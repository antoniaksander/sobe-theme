<?php

/**
 * WooCommerce integration.
 */

namespace App;

if (! class_exists('WooCommerce')) {
    return;
}

/**
 * Declare WooCommerce feature support.
 */
add_action('after_setup_theme', function () {
    add_theme_support('woocommerce');
    // wc-product-gallery-zoom intentionally omitted: Splide owns the gallery DOM;
    // zoom-on-hover JS expects .woocommerce-product-gallery__image which Splide removes.
    // Click-to-PhotoSwipe in product-gallery.js provides lightbox UX instead.
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10);
}, 20);

/**
 * AJAX cart fragments.
 *
 * WooCommerce calls this filter after every add-to-cart action and on
 * page load (via cart-fragments.js). We return two fragments:
 *
 *   div.sobe-side-cart-content — the scrollable cart item list
 *   span.sobe-cart-count       — the header badge
 *
 * Each key is a CSS selector; WooCommerce jQuery-replaces the matching
 * element in the DOM with the fragment value.
 *
 * @param  array<string, string>  $fragments
 * @return array<string, string>
 */
add_filter('woocommerce_add_to_cart_fragments', function (array $fragments): array {
    $pfx = config('theme.prefix');
    $count = WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;

    $fragments["div.{$pfx}-side-cart-content"] = view('partials.side-cart-content')->render();

    $fragments["span.{$pfx}-cart-count"] = sprintf(
        '<span class="%s-cart-count absolute -top-1 -right-1 size-4 flex items-center justify-center rounded-full bg-accent text-accent-fg text-[10px] font-bold leading-none%s">%d</span>',
        $pfx,
        $count > 0 ? '' : ' hidden',
        $count
    );

    return $fragments;
});

/**
 * Replace WooCommerce's default <div class="woocommerce"> content wrappers
 * with design-system-aligned section markup — equivalent to:
 * <x-section padding="default" width="grid">
 *
 * Blade components can't be used in PHP action hooks directly, so we output
 * the rendered HTML equivalent inline.
 */
add_action('after_setup_theme', function () {
    remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
    remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);
}, 21); // Priority 21 — runs after WC registers its defaults at 20

add_action('woocommerce_before_main_content', function () {
    echo '<section class="py-16 md:py-24 woocommerce"><div class="max-w-standard mx-auto w-full px-6 lg:px-8">';
}, 10);

add_action('woocommerce_after_main_content', function () {
    echo '</div></section>';
}, 10);

/**
 * Enqueue WooCommerce-specific styles only on WooCommerce pages.
 * Priority 100 (after WC registers its styles at 99) so our overrides
 * win in the cascade without needing !important.
 */
add_action('wp_enqueue_scripts', function () {
    if (is_woocommerce() || is_cart() || is_checkout() || is_account_page() || has_block('sobe/product-carousel')) {
        $handle = config('theme.prefix') . '-woocommerce';

        wp_enqueue_style(
            $handle,
            \Roots\asset('resources/css/woocommerce.css')->uri(),
            ['woocommerce-general', 'woocommerce-layout', 'woocommerce-smallscreen'],
            null
        );

        // Inject the gallery aspect-ratio token so forked projects can override it
        // from config/theme.php ('wc_gallery_aspect_ratio') without touching CSS.
        $ratio = sanitize_text_field(config('theme.wc_gallery_aspect_ratio', '1 / 1'));
        wp_add_inline_style($handle, ':root{--pdp-gallery-aspect-ratio:' . $ratio . '}');
    }
}, 100);

add_filter('loop_shop_columns', function (): int {
    $pfx = config('theme.prefix');
    return (int) get_theme_mod("{$pfx}_product_catalog_desktop_columns", 4);
});

add_filter('loop_shop_per_page', function (): int {
    $pfx = config('theme.prefix');
    return (int) get_theme_mod("{$pfx}_products_per_page", 12);
});

/**
 * Load WooCommerce frontend scripts conditionally by page context.
 *
 * The blanket WC_Frontend_Scripts::load_scripts() call was loading jQuery
 * (synchronous, head-blocking) + every WC script on every page of the site,
 * causing TBT > 1 s. This replaces it with page-scoped loading:
 *
 * - Product pages: full load_scripts() so variation params/localizations are
 *   injected correctly, then dequeue scripts replaced by the custom Swiper gallery.
 * - Cart/Checkout/Account: unchanged — let WC manage its own dependencies.
 * - Everything else: only wc-cart-fragments for the side-cart fragment refresh.
 *   If the Alpine side cart fully manages state via Store API, remove that line
 *   (sub-step 4b) to eliminate jQuery entirely from non-WC pages.
 */
add_action('wp_enqueue_scripts', function () {
    if (! is_admin() && class_exists('WC_Frontend_Scripts')) {
        if (is_product()) {
            // Full load_scripts() is required here — it also runs wp_localize_script()
            // for wc_single_product_params and wc_add_to_cart_variation_params, which
            // variation dropdowns depend on to update prices and images.
            \WC_Frontend_Scripts::load_scripts();

            // Dequeue scripts this theme replaces with its custom Swiper/PhotoSwipe gallery.
            wp_dequeue_script('flexslider');
            wp_dequeue_script('photoswipe');
            wp_dequeue_script('photoswipe-ui-default');
            wp_dequeue_script('wc-zoom');
            return;
        }

        if (is_cart() || is_checkout() || is_account_page()) {
            \WC_Frontend_Scripts::load_scripts();
            return;
        }

        // Non-WC pages: load only wc-cart-fragments for side-cart fragment refresh.
        // NOTE: wc-cart-fragments depends on jQuery, so jQuery still loads here.
        // If the Alpine side cart manages cart state entirely via Store API responses,
        // remove this line to eliminate jQuery on non-product pages (sub-step 4b).
        wp_enqueue_script('wc-cart-fragments');
    }
}, 99);

add_action('wp_head', function () {
    if (! is_admin() && class_exists('WooCommerce')) {
        $pfx = config('theme.prefix');
        $params = [
            'ajaxUrl'          => admin_url('admin-ajax.php'),
            'ajaxAction'       => "{$pfx}_refresh_cart",
            'storeApiNonce'    => wp_create_nonce('wc_store_api'),
            'storeApiCartUrl'  => rest_url('wc/store/v1/cart'),
            'storeApiAddUrl'   => rest_url('wc/store/v1/cart/add-item'),
            'sideCartEnabled'  => (bool) get_theme_mod("{$pfx}_enable_side_cart", true),
            'addedToCartText'  => __('Product added to cart', 'sobe'),
            'cartOpenedText'   => __('Product added to cart. Your cart is now open.', 'sobe'),
            'addToCartErrorText' => __('Could not add product to cart.', 'sobe'),
            'networkErrorText' => __('Something went wrong. Please try again.', 'sobe'),
            'wcAjaxUrl'        => \WC_AJAX::get_endpoint('%%endpoint%%'),
        ];
        echo '<script>window.themeCartParams = ' . \wp_json_encode($params) . ';</script>';
    }
}, 5);

add_filter('body_class', function (array $classes): array {
    if (! (is_shop() || is_product_taxonomy())) {
        return $classes;
    }

    $pfx = config('theme.prefix');

    $mobileColumns = get_theme_mod("{$pfx}_product_catalog_mobile_columns", '2');
    if (! in_array($mobileColumns, ['1', '2'], true)) {
        $mobileColumns = '2';
    }

    $tabletColumns = get_theme_mod("{$pfx}_product_catalog_tablet_columns", '3');
    if (! in_array($tabletColumns, ['1', '2', '3'], true)) {
        $tabletColumns = '3';
    }

    $desktopColumns = get_theme_mod("{$pfx}_product_catalog_desktop_columns", '4');
    if (! in_array($desktopColumns, ['1', '2', '3', '4', '5', '6'], true)) {
        $desktopColumns = '4';
    }

    $classes[] = "{$pfx}-catalog-mobile-columns-{$mobileColumns}";
    $classes[] = "{$pfx}-catalog-tablet-columns-{$tabletColumns}";
    $classes[] = "{$pfx}-catalog-desktop-columns-{$desktopColumns}";

    return $classes;
});

/**
 * Hybrid Model hook management.
 *
 * Runs at priority 22 so WooCommerce's template hooks (loaded at priority 10
 * via wc-template-hooks.php) are already registered when we remove/add them.
 *
 * Tier 1/2 removals: WC's own callbacks are removed so Blade templates own
 * the structural DOM shell. The do_action() calls remain in the templates —
 * the hook bus stays open for plugins (quick-view, badges, BNPL, etc.).
 */
add_action('after_setup_theme', function () {
    $pfx = config('theme.prefix');

    // ── Tier 1/2: Blade owns the product card DOM shell ────────────────────

    // Link wrappers — Blade writes <a> in Zone A and closes it there.
    remove_action('woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10);
    remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5);

    // Image — Blade renders woocommerce_get_product_thumbnail() in Zone A.
    // Hover-swap dual-image logic lives in content-product.blade.php.
    remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10);

    // Sale badge — rendered directly in content-product.blade.php.
    remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10);

    // Title — Blade renders <h2> wrapped in its own <a href> in Zone B.
    remove_action('woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10);

    // Add-to-cart button removed from all product cards globally.
    remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);

    // ── Tier 1: Blade owns the PDP title ───────────────────────────────────
    // content-single-product.blade.php renders <h1> above woocommerce_single_product_summary.
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_title', 5);

    // ── Tier 1: Blade owns the PDP gallery (Splide replaces WC FlexSlider) ──
    // Splide markup renders directly in content-single-product.blade.php.
    // Hook bus stays open — sale flash and other plugins still fire.
    remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);

    // ── Short description moved to Row 2, Col 1 of the 2-row PDP grid ───────
    // Rendered via wc_format_content() in content-single-product.blade.php.
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);

    // ── Brand label ─────────────────────────────────────────────────────────
    // Fires at priority 5, before the title link rendered by Blade.
    add_action('woocommerce_shop_loop_item_title', function () {
        global $product;
        $terms = get_the_terms($product->get_id(), 'product_brand');
        if ($terms && ! is_wp_error($terms)) {
            echo '<span class="product-brand">'.esc_html($terms[0]->name).'</span>';
        }
    }, 5);

    // Stars at priority 4 appear before price (priority 10) in Zone B.
    remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5);
    add_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 4);
}, 22);

/**
 * Extra product tabs — Shipping Information and Product Details (Misc).
 *
 * Added at priority 20 so they appear after WooCommerce's built-in tabs
 * (Description 10, Additional Info 20, Reviews 30). The Shipping copy is
 * wrapped in apply_filters() so child themes or plugins can swap it out
 * without editing theme files.
 */
add_filter('woocommerce_product_tabs', function (array $tabs): array {
    $tabs['shipping_info'] = [
        'title'    => __('Shipping Information', 'sobe'),
        'priority' => 50,
        'callback' => function (): void {
            echo '<p>' . wp_kses_post(
                apply_filters(
                    'sobe_shipping_info_text',
                    __('Free standard shipping on all orders over $100. Express delivery available at checkout.', 'sobe')
                )
            ) . '</p>';
        },
    ];

    $tabs['misc'] = [
        'title'    => __('Product Details', 'sobe'),
        'priority' => 60,
        'callback' => function (): void {
            global $product;
            $sku  = $product->get_sku();
            $cats = wc_get_product_category_list($product->get_id(), ', ');
            $tags = wc_get_product_tag_list($product->get_id(), ', ');
            echo '<dl class="pdp-misc-list">';
            if ($sku) {
                printf('<dt>%s</dt><dd>%s</dd>', esc_html__('SKU', 'sobe'), esc_html($sku));
            }
            if ($cats) {
                printf('<dt>%s</dt><dd>%s</dd>', esc_html__('Categories', 'sobe'), wp_kses_post($cats));
            }
            if ($tags) {
                printf('<dt>%s</dt><dd>%s</dd>', esc_html__('Tags', 'sobe'), wp_kses_post($tags));
            }
            echo '</dl>';
        },
    ];

    return $tabs;
}, 20);
/**
 * Enqueue Swiper product gallery on single product pages only.
 *
 * jQuery is listed as a dependency because the variation-switching code in
 * product-gallery.js uses jQuery to listen for WooCommerce's found_variation
 * jQuery event (WC fires it via $.trigger, not native dispatchEvent).
 */
add_action('wp_enqueue_scripts', function (): void {
    if (! is_product()) {
        return;
    }
    wp_enqueue_script(
        config('theme.prefix') . '-product-gallery',
        \Roots\asset('resources/js/product-gallery.js')->uri(),
        ['jquery'],
        null,
        true
    );
});

// ── Shop pagination ────────────────────────────────────────────────────────

add_action('after_setup_theme', function (): void {
    remove_action('woocommerce_after_shop_loop', 'woocommerce_pagination', 10);
    add_action('woocommerce_after_shop_loop', function (): void {
        echo view('woocommerce.loop.pagination')->render();
    }, 10);
}, 22);

add_action('wp_enqueue_scripts', function (): void {
    if (! (is_shop() || is_product_taxonomy() || is_product_tag())) {
        return;
    }

    $pfx          = config('theme.prefix');
    $mode         = get_theme_mod("{$pfx}_shop_pagination_mode", 'paginated');
    $ordering     = WC()->query ? WC()->query->get_catalog_ordering_args() : [];
    $queried      = get_queried_object();

    $params = [
        'ajaxUrl'        => admin_url('admin-ajax.php'),
        'ajaxAction'     => "{$pfx}_load_more_products",
        'nonce'          => wp_create_nonce("{$pfx}_load_more"),
        'historyEnabled' => (bool) get_theme_mod("{$pfx}_pagination_history", false),
        'taxonomy'       => is_product_taxonomy() ? sanitize_key($queried->taxonomy ?? '') : '',
        'termId'         => is_product_taxonomy() ? (int) ($queried->term_id ?? 0) : 0,
        'search'         => sanitize_text_field(get_search_query()),
        'orderby'        => sanitize_key($ordering['orderby'] ?? 'menu_order'),
        'loadingText'    => __('Loading products…', 'sobe'),
        'loadedText'     => __('More products loaded', 'sobe'),
    ];
    echo '<script>window.sobeLoadMoreParams = ' . \wp_json_encode($params) . ';</script>';

    if ($mode !== 'load-more') {
        return;
    }

    wp_enqueue_script(
        "{$pfx}-shop-load-more",
        \Roots\asset('resources/js/shop-load-more.js')->uri(),
        [],
        null,
        true
    );
}, 20);

$load_more_handler = function (): void {
    $pfx = config('theme.prefix');
    check_ajax_referer("{$pfx}_load_more", 'nonce');

    $page     = max(1, (int) ($_POST['page'] ?? 1));
    $taxonomy = sanitize_key($_POST['taxonomy'] ?? '');
    $term_id  = (int) ($_POST['term_id'] ?? 0);
    $search   = sanitize_text_field($_POST['search'] ?? '');
    $orderby  = sanitize_key($_POST['orderby'] ?? 'menu_order');
    $per_page = (int) get_theme_mod("{$pfx}_products_per_page", 12);

    $query_args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'paged'          => $page,
        'posts_per_page' => $per_page,
        'orderby'        => $orderby,
    ];

    if ($taxonomy && $term_id) {
        $query_args['tax_query'] = [[
            'taxonomy' => $taxonomy,
            'field'    => 'term_id',
            'terms'    => $term_id,
        ]];
    }

    if ($search) {
        $query_args['s'] = $search;
    }

    $query = new \WP_Query($query_args);

    ob_start();
    if ($query->have_posts()) {
        wc_setup_loop([
            'columns' => (int) get_theme_mod("{$pfx}_product_catalog_desktop_columns", 4),
        ]);
        while ($query->have_posts()) {
            $query->the_post();
            wc_get_template_part('content', 'product');
        }
        wc_reset_loop();
    }
    wp_reset_postdata();
    $html = ob_get_clean();

    wp_send_json([
        'html'     => $html,
        'has_more' => $page < $query->max_num_pages,
        'next_page' => $page + 1,
    ]);
};

$load_more_action = config('theme.prefix') . '_load_more_products';
add_action("wp_ajax_{$load_more_action}", $load_more_handler);
add_action("wp_ajax_nopriv_{$load_more_action}", $load_more_handler);

// ── Cart refresh ────────────────────────────────────────────────────────────

$refresh_cart_handler = function () {
    check_ajax_referer('wc_store_api');
    if (! defined('DOING_AJAX')) {
        define('DOING_AJAX', true);
    }
    WC()->cart->calculate_totals();
    echo view('partials.side-cart-content')->render();
    wp_die();
};

$refresh_action = config('theme.prefix') . '_refresh_cart';
add_action("wp_ajax_{$refresh_action}", $refresh_cart_handler);
add_action("wp_ajax_nopriv_{$refresh_action}", $refresh_cart_handler);

// ── Catalog filter helpers ────────────────────────────────────────────────────

/**
 * Pluggable swatch colour fallback chain.
 *
 * 1. Native theme meta  (sobe_swatch_value)
 * 2. YITH WC Swatches   (yith_wccl_value)
 * 3. Generic colour hex (pa_color_hex)
 * 4. Developer escape hatch via filter
 */
function sobe_get_swatch_value(\WP_Term $term, string $attribute_name): ?string
{
    $id = $term->term_id;
    if ($v = get_term_meta($id, 'sobe_swatch_value', true)) {
        return (string) $v;
    }
    if ($v = get_term_meta($id, 'yith_wccl_value', true)) {
        return (string) $v;
    }
    if ($v = get_term_meta($id, 'pa_color_hex', true)) {
        return (string) $v;
    }
    return apply_filters('sobe_swatch_value', null, $term, $attribute_name);
}

// ── AJAX catalog filter handler ───────────────────────────────────────────────

$filter_handler = function (): void {
    $pfx = config('theme.prefix');
    check_ajax_referer("{$pfx}_nonce", 'nonce');

    $raw_state   = sanitize_text_field(wp_unslash($_POST['filter_state'] ?? '{}'));
    $filter_state = json_decode($raw_state, true) ?: [];

    $per_page = (int) get_theme_mod("{$pfx}_products_per_page", 12);
    $paged    = max(1, (int) ($filter_state['paged'] ?? 1));

    $query_args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $paged,
    ];

    // Tax query — categories (single) + filter_* attributes
    $tax_query = [];

    if (! empty($filter_state['product_cat'])) {
        $tax_query[] = [
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => sanitize_text_field($filter_state['product_cat']),
        ];
    }

    foreach ($filter_state as $key => $val) {
        if (strpos($key, 'filter_') !== 0) {
            continue;
        }
        $attr_name = substr($key, 7);
        $taxonomy  = 'pa_' . sanitize_key($attr_name);
        $slugs     = array_map('sanitize_text_field', (array) $val);
        if (! empty($slugs)) {
            $tax_query[] = [
                'taxonomy' => $taxonomy,
                'field'    => 'slug',
                'terms'    => $slugs,
                'operator' => 'IN',
            ];
        }
    }

    if (! empty($filter_state[$pfx . '_brands']) || ! empty($filter_state['product_brand'])) {
        $brand_key = $pfx . '_brands';
        $slugs = array_map('sanitize_text_field', (array) ($filter_state[$brand_key] ?? $filter_state['product_brand'] ?? []));
        if (! empty($slugs)) {
            $tax_query[] = [
                'taxonomy' => 'product_brand',
                'field'    => 'slug',
                'terms'    => $slugs,
                'operator' => 'IN',
            ];
        }
    }

    if (count($tax_query) > 1) {
        $tax_query['relation'] = 'AND';
    }
    if (! empty($tax_query)) {
        $query_args['tax_query'] = $tax_query;
    }

    // Price meta query
    $meta_query = [];
    $min_price  = isset($filter_state['min_price']) ? (float) $filter_state['min_price'] : null;
    $max_price  = isset($filter_state['max_price']) ? (float) $filter_state['max_price'] : null;

    if ($min_price !== null || $max_price !== null) {
        $price_clause = ['key' => '_price', 'type' => 'NUMERIC'];
        if ($min_price !== null && $max_price !== null) {
            $price_clause['value']   = [$min_price, $max_price];
            $price_clause['compare'] = 'BETWEEN';
        } elseif ($min_price !== null) {
            $price_clause['value']   = $min_price;
            $price_clause['compare'] = '>=';
        } else {
            $price_clause['value']   = $max_price;
            $price_clause['compare'] = '<=';
        }
        $meta_query[] = $price_clause;
    }
    if (! empty($meta_query)) {
        $query_args['meta_query'] = $meta_query;
    }

    $query = new \WP_Query($query_args);

    ob_start();
    if ($query->have_posts()) {
        wc_setup_loop(['columns' => (int) get_theme_mod("{$pfx}_product_catalog_desktop_columns", 4)]);
        while ($query->have_posts()) {
            $query->the_post();
            wc_get_template_part('content', 'product');
        }
        wc_reset_loop();
    }
    wp_reset_postdata();
    $html = ob_get_clean();

    // Pagination HTML
    $GLOBALS['wp_query'] = $query;
    $pagination_html = view('woocommerce.loop.pagination')->render();

    wp_send_json([
        'html'            => $html,
        'pagination_html' => $pagination_html,
        'count'           => $query->found_posts,
    ]);
};

$filter_action = config('theme.prefix') . '_filter_products';
add_action("wp_ajax_{$filter_action}", $filter_handler);
add_action("wp_ajax_nopriv_{$filter_action}", $filter_handler);

// Inline sobeCatalogParams on shop/taxonomy pages
add_action('wp_enqueue_scripts', function (): void {
    if (! is_shop() && ! is_product_taxonomy()) {
        return;
    }
    $pfx = config('theme.prefix');
    $params = [
        'ajaxUrl'     => admin_url('admin-ajax.php'),
        'nonce'       => wp_create_nonce("{$pfx}_nonce"),
        'action'      => "{$pfx}_filter_products",
        'removeLabel' => __('Remove filter', 'sobe'),
    ];
    wp_add_inline_script(
        'sobe-app',
        'window.sobeCatalogParams = ' . wp_json_encode($params) . ';',
        'before'
    );
}, 20);

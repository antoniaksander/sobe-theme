<?php

/**

 * Demo product-detail presentation policy.

 */



namespace App;



if (! class_exists('WooCommerce')) {

    return;

}



add_action('after_setup_theme', function () {
    add_theme_support('woocommerce');
    // wc-product-gallery-zoom intentionally omitted: Splide owns the gallery DOM;
    // zoom-on-hover JS expects .woocommerce-product-gallery__image which Splide removes.
    // Click-to-PhotoSwipe in product-gallery.js provides lightbox UX instead.
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10);
}, 20);

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
        'title' => __('Shipping Information', 'sobe'),
        'priority' => 50,
        'callback' => function (): void {
            echo '<p>'.wp_kses_post(
                apply_filters(
                    'sobe_shipping_info_text',
                    __('Free standard shipping on all orders over $100. Express delivery available at checkout.', 'sobe')
                )
            ).'</p>';
        },
    ];

    $tabs['misc'] = [
        'title' => __('Product Details', 'sobe'),
        'priority' => 60,
        'callback' => function (): void {
            global $product;
            $sku = $product->get_sku();
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
        config('theme.prefix').'-product-gallery',
        \Roots\asset('resources/js/product-gallery.js')->uri(),
        ['jquery'],
        null,
        true
    );
});

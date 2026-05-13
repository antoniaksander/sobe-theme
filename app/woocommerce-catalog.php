<?php

/**

 * Demo catalog and load-more pagination policy.

 */



namespace App;



use function Roots\view;



if (! class_exists('WooCommerce')) {

    return;

}



add_filter('loop_shop_columns', function (): int {
    $pfx = config('theme.prefix');

    return (int) get_theme_mod("{$pfx}_product_catalog_desktop_columns", 4);
});

add_filter('loop_shop_per_page', function (): int {
    $pfx = config('theme.prefix');

    return (int) get_theme_mod("{$pfx}_products_per_page", 12);
});

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

    $pfx = config('theme.prefix');
    $mode = get_theme_mod("{$pfx}_shop_pagination_mode", 'paginated');
    $ordering = WC()->query ? WC()->query->get_catalog_ordering_args() : [];
    $queried = get_queried_object();
    // WC may return a space-separated compound orderby (e.g. 'menu_order title'); take the first token only.
    $orderby_raw = explode(' ', $ordering['orderby'] ?? 'menu_order')[0];
    $orderby = sanitize_key($orderby_raw) ?: 'menu_order';

    $params = [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'ajaxAction' => "{$pfx}_load_more_products",
        'nonce' => wp_create_nonce("{$pfx}_load_more"),
        'historyEnabled' => (bool) get_theme_mod("{$pfx}_pagination_history", false),
        'taxonomy' => is_product_taxonomy() ? sanitize_key($queried->taxonomy ?? '') : '',
        'termId' => is_product_taxonomy() ? (int) ($queried->term_id ?? 0) : 0,
        'search' => sanitize_text_field(get_search_query()),
        'orderby' => $orderby,
        'loadingText' => __('Loading products…', 'sobe'),
        'loadedText' => __('More products loaded', 'sobe'),
        'errorText' => __('Failed to load more products. Please refresh the page.', 'sobe'),
    ];
    echo '<script>window.sobeLoadMoreParams = '.\wp_json_encode($params).';</script>';

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

    $page = max(1, (int) ($_POST['page'] ?? 1));
    $taxonomy = sanitize_key($_POST['taxonomy'] ?? '');
    $term_id = (int) ($_POST['term_id'] ?? 0);
    $search = sanitize_text_field($_POST['search'] ?? '');
    $orderby = sanitize_key($_POST['orderby'] ?? 'menu_order');
    $per_page = (int) get_theme_mod("{$pfx}_products_per_page", 12);

    $query_args = [
        'post_type' => 'product',
        'post_status' => 'publish',
        'paged' => $page,
        'posts_per_page' => $per_page,
        'orderby' => $orderby,
    ];

    if ($taxonomy && $term_id) {
        $query_args['tax_query'] = [[
            'taxonomy' => $taxonomy,
            'field' => 'term_id',
            'terms' => $term_id,
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
        'html' => $html,
        'has_more' => $page < $query->max_num_pages,
        'next_page' => $page + 1,
    ]);
};

$load_more_action = config('theme.prefix').'_load_more_products';
add_action("wp_ajax_{$load_more_action}", $load_more_handler);
add_action("wp_ajax_nopriv_{$load_more_action}", $load_more_handler);

<?php

/**
 * Customizer policy.
 */

namespace App;

/**
 * Register Theme Options in the Customizer.
 *
 * @return void
 */
add_action('customize_register', function (\WP_Customize_Manager $wp_customize) {
    $pfx = config('theme.prefix');

    $wp_customize->add_section("{$pfx}_header_options", [
        'title' => __('Header Options', 'sobe'),
        'priority' => 30,
    ]);

    // ── Header ─────────────────────────────────────────────────────────────
    $wp_customize->add_setting("{$pfx}_header_layout", [
        'default' => 'header-1',
        'sanitize_callback' => function ($value) {
            $allowed = ['header-1', 'header-2', 'header-3'];

            return in_array($value, $allowed, true) ? $value : 'header-1';
        },
        'transport' => 'refresh',
    ]);

    $wp_customize->add_control("{$pfx}_header_layout", [
        'label' => __('Header: Layout', 'sobe'),
        'section' => "{$pfx}_header_options",
        'type' => 'select',
        'choices' => [
            'header-1' => __('Header 1 (Default)', 'sobe'),
            'header-2' => __('Header 2', 'sobe'),
            'header-3' => __('Header 3', 'sobe'),
        ],
    ]);

    $wp_customize->add_setting("{$pfx}_enable_dark_toggle", [
        'default' => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
        'transport' => 'refresh',
    ]);

    $wp_customize->add_control("{$pfx}_enable_dark_toggle", [
        'label' => __('Header: Dark Mode Toggle', 'sobe'),
        'description' => __('Shows a sun/moon button in the site header.', 'sobe'),
        'section' => "{$pfx}_header_options",
        'type' => 'checkbox',
    ]);

    $wp_customize->add_setting("{$pfx}_enable_side_cart", [
        'default' => true,
        'sanitize_callback' => 'rest_sanitize_boolean',
        'transport' => 'refresh',
    ]);

    $wp_customize->add_control("{$pfx}_enable_side_cart", [
        'label' => __('Header: Side Cart', 'sobe'),
        'description' => __('Shows a cart button in the site header.', 'sobe'),
        'section' => "{$pfx}_header_options",
        'type' => 'checkbox',
    ]);

    $wp_customize->add_setting("{$pfx}_header_wishlist", [
        'default' => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
        'transport' => 'refresh',
    ]);

    $wp_customize->add_control("{$pfx}_header_wishlist", [
        'label' => __('Header: Wishlist Icon', 'sobe'),
        'description' => __('Shows a heart icon in the site header when a wishlist provider is available.', 'sobe'),
        'section' => "{$pfx}_header_options",
        'type' => 'checkbox',
    ]);

    // ── Logo ────────────────────────────────────────────────────────────────
    $wp_customize->add_setting("{$pfx}_logo", [
        'default' => '',
        'sanitize_callback' => 'absint',
        'transport' => 'refresh',
    ]);

    $wp_customize->add_control(new \WP_Customize_Media_Control($wp_customize, "{$pfx}_logo", [
        'label' => __('Logo: Light', 'sobe'),
        'description' => __('Logo for light/white backgrounds. Recommended: transparent PNG or SVG.', 'sobe'),
        'section' => "{$pfx}_header_options",
        'mime_type' => 'image',
    ]));

    $wp_customize->add_setting("{$pfx}_dark_logo", [
        'default' => '',
        'sanitize_callback' => 'absint',
        'transport' => 'refresh',
    ]);

    $wp_customize->add_control(new \WP_Customize_Media_Control($wp_customize, "{$pfx}_dark_logo", [
        'label' => __('Logo: Dark', 'sobe'),
        'description' => __('Logo for dark backgrounds (dark mode). Recommended: transparent PNG or SVG.', 'sobe'),
        'section' => "{$pfx}_header_options",
        'mime_type' => 'image',
    ]));

    // ── Footer ─────────────────────────────────────────────────────────────
    $wp_customize->add_section("{$pfx}_footer_options", [
        'title' => __('Footer Options', 'sobe'),
        'priority' => 31,
    ]);

    $wp_customize->add_setting("{$pfx}_footer_layout", [
        'default' => 'layout-2',
        'sanitize_callback' => function ($value) {
            $allowed = ['layout-2', 'none'];

            return in_array($value, $allowed, true) ? $value : 'layout-2';
        },
        'transport' => 'refresh',
    ]);

    $wp_customize->add_control("{$pfx}_footer_layout", [
        'label' => __('Footer: Layout', 'sobe'),
        'section' => "{$pfx}_footer_options",
        'type' => 'select',
        'choices' => [
            'layout-2' => __('Minimal (Brand + Widgets)', 'sobe'),
            'none' => __('None (Hidden)', 'sobe'),
        ],
    ]);

    $wp_customize->add_setting("{$pfx}_product_card_hover", [
        'default' => 'zoom',
        'sanitize_callback' => function ($value) {
            return in_array($value, ['zoom', 'swap'], true) ? $value : 'zoom';
        },
        'transport' => 'refresh',
    ]);

    $wp_customize->add_control("{$pfx}_product_card_hover", [
        'label' => __('Product Card: Image Hover Effect', 'sobe'),
        'section' => 'woocommerce_product_catalog',
        'type' => 'select',
        'choices' => [
            'zoom' => __('Zoom In', 'sobe'),
            'swap' => __('Swap to Gallery Image', 'sobe'),
        ],
    ]);

    $wp_customize->add_setting("{$pfx}_product_catalog_mobile_columns", [
        'default' => (string) config('theme.product_catalog.mobile_columns', 1),
        'sanitize_callback' => function ($value) {
            $fallback = (string) config('theme.product_catalog.mobile_columns', 1);

            return in_array($value, ['1', '2'], true) ? $value : $fallback;
        },
        'transport' => 'refresh',
    ]);

    $wp_customize->add_control("{$pfx}_product_catalog_mobile_columns", [
        'label' => __('Product Catalog: Mobile Columns', 'sobe'),
        'description' => __('Choose how many products appear per row on mobile screens.', 'sobe'),
        'section' => 'woocommerce_product_catalog',
        'type' => 'select',
        'choices' => [
            '1' => __('1 item per row (Default)', 'sobe'),
            '2' => __('2 items per row', 'sobe'),
        ],
    ]);

    $wp_customize->add_setting("{$pfx}_product_catalog_tablet_columns", [
        'default' => (string) config('theme.product_catalog.tablet_columns', 3),
        'sanitize_callback' => function ($value) {
            $fallback = (string) config('theme.product_catalog.tablet_columns', 3);

            return in_array($value, ['1', '2', '3'], true) ? $value : $fallback;
        },
        'transport' => 'refresh',
    ]);

    $wp_customize->add_control("{$pfx}_product_catalog_tablet_columns", [
        'label' => __('Product Catalog: Tablet Columns', 'sobe'),
        'description' => __('Choose how many products appear per row on tablet screens.', 'sobe'),
        'section' => 'woocommerce_product_catalog',
        'type' => 'select',
        'choices' => [
            '1' => __('1 item per row', 'sobe'),
            '2' => __('2 items per row', 'sobe'),
            '3' => __('3 items per row (Default)', 'sobe'),
        ],
    ]);

    $wp_customize->add_setting("{$pfx}_product_catalog_desktop_columns", [
        'default' => (string) config('theme.product_catalog.desktop_columns', 3),
        'sanitize_callback' => function ($value) {
            $fallback = (string) config('theme.product_catalog.desktop_columns', 3);

            return in_array($value, ['1', '2', '3', '4', '5', '6'], true) ? $value : $fallback;
        },
        'transport' => 'refresh',
    ]);

    $wp_customize->add_control("{$pfx}_product_catalog_desktop_columns", [
        'label' => __('Product Catalog: Desktop Columns', 'sobe'),
        'description' => __('Choose how many products appear per row on desktop screens.', 'sobe'),
        'section' => 'woocommerce_product_catalog',
        'type' => 'select',
        'choices' => [
            '1' => __('1 item per row', 'sobe'),
            '2' => __('2 items per row', 'sobe'),
            '3' => __('3 items per row (Default)', 'sobe'),
            '4' => __('4 items per row', 'sobe'),
            '5' => __('5 items per row', 'sobe'),
            '6' => __('6 items per row', 'sobe'),
        ],
    ]);

    $wp_customize->add_setting("{$pfx}_products_per_page", [
        'default' => (int) config('theme.product_catalog.per_page', 12),
        'sanitize_callback' => function ($value) {
            $v = (int) $value;
            $fallback = (int) config('theme.product_catalog.per_page', 12);

            return ($v >= 4 && $v <= 48) ? $v : $fallback;
        },
        'transport' => 'refresh',
    ]);

    $wp_customize->add_control("{$pfx}_products_per_page", [
        'label' => __('Product Catalog: Products Per Page', 'sobe'),
        'description' => __('Number of products displayed per page (4–48).', 'sobe'),
        'section' => 'woocommerce_product_catalog',
        'type' => 'number',
        'input_attrs' => [
            'min' => 4,
            'max' => 48,
            'step' => 1,
        ],
    ]);

    // ── Shop Pagination ─────────────────────────────────────────────────
    $wp_customize->add_setting("{$pfx}_shop_pagination_mode", [
        'default' => 'paginated',
        'sanitize_callback' => function ($value) {
            return in_array($value, ['paginated', 'load-more'], true) ? $value : 'paginated';
        },
        'transport' => 'refresh',
    ]);

    $wp_customize->add_control("{$pfx}_shop_pagination_mode", [
        'label' => __('Shop Pagination: Mode', 'sobe'),
        'description' => __('Choose how products paginate on the shop and category pages.', 'sobe'),
        'section' => 'woocommerce_product_catalog',
        'type' => 'select',
        'choices' => [
            'paginated' => __('Classic (Prev / Page X of Y / Next)', 'sobe'),
            'load-more' => __('Load More on Scroll (Infinite Scroll)', 'sobe'),
        ],
    ]);

    $wp_customize->add_setting("{$pfx}_pagination_history", [
        'default' => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
        'transport' => 'refresh',
    ]);

    $wp_customize->add_control("{$pfx}_pagination_history", [
        'label' => __('Shop Pagination: Update URL on Load More', 'sobe'),
        'description' => __('Updates the browser URL (e.g. ?paged=3) as products load, so the back button works. Only applies in Load More mode.', 'sobe'),
        'section' => 'woocommerce_product_catalog',
        'type' => 'checkbox',
    ]);

    // ── WooCommerce Shop Sidebar ────────────────────────────────────────
    $wp_customize->add_setting("{$pfx}_shop_sidebar_enabled", [
        'default' => true,
        'sanitize_callback' => 'rest_sanitize_boolean',
        'transport' => 'refresh',
    ]);

    $wp_customize->add_control("{$pfx}_shop_sidebar_enabled", [
        'label' => __('Shop Page: Sidebar', 'sobe'),
        'description' => __('Show filtering sidebar on shop and product category pages.', 'sobe'),
        'section' => 'woocommerce_product_catalog',
        'type' => 'checkbox',
    ]);
});

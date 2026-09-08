<?php

/**
 * Pattern, meta, taxonomy, font, widget, and fallback setup.
 */

namespace App;

use function Roots\view;



/**
 * Register block pattern category and patterns.
 */
add_action('init', function () {
    // Layout patterns — hidden from inserter; rendered programmatically via \App\sobe_render_layout_pattern().
    register_block_pattern_category('sobe-layout', [
        'label' => __('Sobe Layout', 'sobe'),
    ]);

    register_block_pattern('sobe/header-layout-1', [
        'title' => __('Header Layout 1', 'sobe'),
        'categories' => ['sobe-layout'],
        'inserter' => false,
        'content' => require resource_path('patterns/header-layout-1.php'),
    ]);

    register_block_pattern('sobe/header-layout-2', [
        'title' => __('Header Layout 2', 'sobe'),
        'categories' => ['sobe-layout'],
        'inserter' => false,
        'content' => require resource_path('patterns/header-layout-2.php'),
    ]);

    register_block_pattern('sobe/header-layout-3', [
        'title' => __('Header Layout 3', 'sobe'),
        'categories' => ['sobe-layout'],
        'inserter' => false,
        'content' => require resource_path('patterns/header-layout-3.php'),
    ]);

    register_block_pattern('sobe/footer-layout-2', [
        'title' => __('Footer Layout 2', 'sobe'),
        'categories' => ['sobe-layout'],
        'inserter' => false,
        'content' => require resource_path('patterns/footer-layout-2.php'),
    ]);
});

/**
 * Register page display post meta.
 */
add_action('init', function () {
    $meta = [
        '_sobe_page_hero' => 'boolean',
        '_sobe_hide_title' => 'boolean',
    ];
    foreach ($meta as $key => $type) {
        register_post_meta('page', $key, [
            'show_in_rest' => true,
            'single' => true,
            'type' => $type,
            'default' => false,
            'auth_callback' => fn () => current_user_can('edit_posts'),
        ]);
    }

    register_post_meta('page', '_sobe_page_hero_text', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'default' => '',
        'sanitize_callback' => 'sanitize_textarea_field',
        'auth_callback' => fn () => current_user_can('edit_posts'),
    ]);
});

/**
 * Register post CTA label meta (custom link text for the blog listing).
 */
add_action('init', function () {
    register_post_meta('post', '_sobe_post_cta', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'default' => '',
        'auth_callback' => fn () => current_user_can('edit_posts'),
    ]);
});

/**
 * Register product_brand taxonomy — fallback only.
 *
 * WooCommerce's native Brands feature (enabled by default since WooCommerce
 * 9.6, shipped behind a feature flag from 9.4) registers its own taxonomy of
 * the same name via `WC_Brands::init_taxonomy()`, hooked to `init` at
 * priority 5 (through the `woocommerce_register_taxonomy` action fired from
 * `WC_Post_types::register_taxonomies()`). That runs before this callback's
 * default `init` priority (10), so by the time this fires, `taxonomy_exists()`
 * already reflects whether WooCommerce has claimed `product_brand`.
 *
 * Sobe only steps in when nothing else has registered it — an older
 * WooCommerce, or the native feature explicitly disabled
 * (`wc_feature_woocommerce_brands_enabled` option) — so every call site that
 * reads the `product_brand` taxonomy (filters, archive hero, breadcrumbs,
 * search) keeps working either way, and WooCommerce's own registration is
 * never silently overwritten when it's present.
 */
add_action('init', 'App\\sobe_register_product_brand_taxonomy');

function sobe_register_product_brand_taxonomy(): void
{
    if (! apply_filters('sobe/product_brand/register', true)) {
        return;
    }

    if (taxonomy_exists('product_brand')) {
        return;
    }

    register_taxonomy('product_brand', 'product', [
        'label' => __('Brands', 'sobe'),
        'labels' => [
            'name' => __('Brands', 'sobe'),
            'singular_name' => __('Brand', 'sobe'),
            'add_new_item' => __('Add New Brand', 'sobe'),
            'edit_item' => __('Edit Brand', 'sobe'),
        ],
        'public' => true,
        'hierarchical' => false,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'brand'],
    ]);
}

/**
 * Preload variable fonts before the @font-face declaration fires.
 * Priority 0 ensures preload hints land before all other wp_head output,
 * letting the browser start fetching fonts during HTML parse rather than
 * waiting for CSS parse — reduces FOUT window and CLS from font-swap reflow.
 *
 * Guards with file_exists() so forked projects that replace the boilerplate
 * fonts don't emit broken preload links pointing to missing files.
 *
 * @return void
 */
add_action('wp_head', function () {
    $themeDir = get_stylesheet_directory();
    $themeUrl = get_stylesheet_directory_uri();
    $fonts = ['Satoshi-Variable.woff2', 'CabinetGrotesk-Variable.woff2'];

    foreach ($fonts as $font) {
        if (file_exists($themeDir.'/fonts/'.$font)) {
            echo '<link rel="preload" as="font" type="font/woff2" crossorigin href="'.esc_attr($themeUrl.'/fonts/'.$font).'">';
        }
    }
}, 0);

/**
 * Inline @font-face declarations for fonts bundled at <theme>/fonts/.
 *
 * Guards with file_exists() so forked projects that swap out the boilerplate
 * fonts (or move them to a CDN) don't emit broken @font-face src() URLs.
 *
 * @return void
 */
add_action('wp_head', function () {
    $themeDir = get_stylesheet_directory();
    $themeUrl = get_stylesheet_directory_uri();
    $faces = '';

    if (file_exists($themeDir.'/fonts/Satoshi-Variable.woff2')) {
        $url = $themeUrl.'/fonts/Satoshi-Variable.woff2';
        $faces .= "@font-face{font-family:'Satoshi';src:url('{$url}')format('woff2');font-weight:300 900;font-display:swap;font-style:normal}";
    }

    if (file_exists($themeDir.'/fonts/CabinetGrotesk-Variable.woff2')) {
        $url = $themeUrl.'/fonts/CabinetGrotesk-Variable.woff2';
        $faces .= "@font-face{font-family:'CabinetGrotesk';src:url('{$url}')format('woff2');font-weight:100 900;font-display:swap;font-style:normal}";
    }

    if ($faces) {
        echo '<style>'.$faces.'</style>';
    }
}, 1);

/**
 * Shortcode: [sobe_dark_toggle]
 * Renders the dark mode toggle anywhere — menus, widgets, content blocks.
 * Returns empty string when the Customizer master switch is off (upsell gate).
 */
add_shortcode(config('theme.prefix').'_dark_toggle', function () {
    if (! get_theme_mod(config('theme.prefix').'_enable_dark_toggle', false)) {
        return '';
    }

    return view('components.dark-mode-toggle')->render();
});

/**
 * Register the theme sidebars.
 *
 * @return void
 */
add_action('widgets_init', function () {
    $config = [
        'before_widget' => '<section class="widget %1$s %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h3>',
        'after_title' => '</h3>',
    ];

    register_sidebar([
        'name' => __('Primary', 'sobe'),
        'id' => 'sidebar-primary',
    ] + $config);

    register_sidebar([
        'name' => __('Footer', 'sobe'),
        'id' => 'sidebar-footer',
    ] + $config);

    register_sidebar([
        'name' => __('Shop Sidebar', 'sobe'),
        'id' => 'sidebar-shop',
    ] + $config);
});

/**
 * Fallback for YITH Wishlist Shortcode
 * Prevents raw shortcode text from leaking on the frontend if the plugin is deactivated.
 */
add_action('init', function () {
    // If the YITH Wishlist plugin is NOT active, take over its shortcode
    if (! defined('YITH_WCWL')) {
        add_shortcode('yith_wcwl_wishlist', function () {
            return '<div class="woocommerce-info">The wishlist feature is currently unavailable. Please activate the YITH Wishlist plugin.</div>';
        });
    }
});

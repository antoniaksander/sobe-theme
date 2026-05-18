<?php

/**
 * Generic WooCommerce support.
 */

namespace App;

if (! class_exists('WooCommerce')) {
    return;
}

add_action('after_setup_theme', function (): void {
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
}, 20);

add_action('after_setup_theme', function (): void {
    remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
    remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);
}, 21);

add_action('woocommerce_before_main_content', function (): void {
    echo '<section class="py-16 md:py-24 woocommerce"><div class="max-w-standard mx-auto w-full px-6 lg:px-8">';
}, 10);

add_action('woocommerce_after_main_content', function (): void {
    echo '</div></section>';
}, 10);

if (! function_exists('App\sobe_has_woocommerce_render_content')) {
    function sobe_has_woocommerce_render_content(): bool
    {
        $woocommerceShortcodes = [
            'products',
            'product_category',
            'featured_products',
            'sale_products',
            'best_selling_products',
            'recent_products',
            'top_rated_products',
            'product',
            'product_page',
        ];

        $post = get_post();
        $content = $post instanceof \WP_Post ? $post->post_content : '';

        if ($content !== '') {
            foreach ($woocommerceShortcodes as $shortcode) {
                if (has_shortcode($content, $shortcode)) {
                    return true;
                }
            }
        }

        $woocommerceBlocks = [
            'sobe/product-carousel',
            'woocommerce/product-filters',
            'woocommerce/active-filters',
            'woocommerce/all-products',
            'woocommerce/product-search',
            'woocommerce/handpicked-products',
            'woocommerce/product-collection',
            'woocommerce/featured-product',
            'woocommerce/featured-category',
            'woocommerce/product-categories',
            'woocommerce/catalog-sorting',
            'woocommerce/product-results-count',
        ];

        foreach ($woocommerceBlocks as $block) {
            if (has_block($block)) {
                return true;
            }
        }

        return false;
    }
}

add_action('wp_enqueue_scripts', function (): void {
    if (! (is_woocommerce() || is_cart() || is_checkout() || is_account_page() || sobe_has_woocommerce_render_content())) {
        return;
    }

    $handle = config('theme.prefix').'-woocommerce';

    wp_enqueue_style(
        $handle,
        \Roots\asset('resources/css/woocommerce.css')->uri(),
        ['woocommerce-general', 'woocommerce-layout', 'woocommerce-smallscreen'],
        null
    );

    $ratio = sanitize_text_field(config('theme.wc_gallery_aspect_ratio', '1 / 1'));
    wp_add_inline_style($handle, ':root{--pdp-gallery-aspect-ratio:'.$ratio.'}');
}, 100);

add_action('wp_enqueue_scripts', function (): void {
    if (is_admin() || ! class_exists('WC_Frontend_Scripts')) {
        return;
    }

    if (is_product()) {
        \WC_Frontend_Scripts::load_scripts();

        return;
    }

    if (is_cart() || is_checkout() || is_account_page()) {
        \WC_Frontend_Scripts::load_scripts();

        return;
    }

    wp_enqueue_script('wc-cart-fragments');
}, 99);

<?php

/**

 * Demo side-cart and cart notice policy.

 */



namespace App;



use function Roots\view;



if (! class_exists('WooCommerce')) {

    return;

}



/**
 * Store Notice Interception (Hybrid A+B Architecture)
 * Captures WC notices, suppresses redundant cart notices when Side Cart is active,
 * and bridges data to Alpine.js toast manager.
 */
add_action('init', function () {
    // Include the notice helpers
    $helper_path = get_theme_file_path('app/Helpers/notice-helpers.php');
    if (file_exists($helper_path)) {
        require_once $helper_path;
    }
});

// Intercept AJAX cart fragments to suppress notices when Side Cart is active
add_filter('woocommerce_add_to_cart_fragments', function ($fragments) {
    $sideCartEnabled = \App\Helpers\sobe_is_side_cart_enabled();

    if ($sideCartEnabled) {
        $emptyWrapper = \App\Helpers\sobe_get_empty_notices_wrapper();
        $fragments['div.woocommerce-notices-wrapper'] = $emptyWrapper;
        $fragments['.woocommerce-notices-wrapper'] = $emptyWrapper;
    } else {
        $notices = \App\Helpers\sobe_get_notices_for_toast();
        if (! empty($notices)) {
            $fragments['sobe_toast_data'] = \wp_json_encode($notices);
        }
    }

    return $fragments;
});

add_filter('wc_add_to_cart_message_html', function ($message) {
    if (\App\Helpers\sobe_is_side_cart_enabled()) {
        return '';
    }

    return $message;
}, 10, 1);

add_filter('woocommerce_add_to_cart_redirect', function ($url) {
    if (! \App\Helpers\sobe_is_side_cart_enabled() || \wp_doing_ajax() || ! \is_product()) {
        return $url;
    }

    $redirectUrl = $url ?: \wp_get_referer() ?: \home_url(\add_query_arg([]));
    $redirectUrl = \remove_query_arg('added-to-cart', $redirectUrl);

    return \add_query_arg('sobe_open_cart', '1', $redirectUrl);
}, 99);

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

add_action('wp_head', function () {
    if (! is_admin() && class_exists('WooCommerce')) {
        $pfx = config('theme.prefix');
        $params = [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'ajaxAction' => "{$pfx}_refresh_cart",
            'storeApiNonce' => wp_create_nonce('wc_store_api'),
            'storeApiCartUrl' => rest_url('wc/store/v1/cart'),
            'storeApiAddUrl' => rest_url('wc/store/v1/cart/add-item'),
            'sideCartEnabled' => (bool) get_theme_mod("{$pfx}_enable_side_cart", true),
            'addedToCartText' => __('Product added to cart', 'sobe'),
            'cartOpenedText' => __('Product added to cart. Your cart is now open.', 'sobe'),
            'addToCartErrorText' => __('Could not add product to cart.', 'sobe'),
            'networkErrorText' => __('Something went wrong. Please try again.', 'sobe'),
            'wcAjaxUrl' => \WC_AJAX::get_endpoint('%%endpoint%%'),
        ];
        echo '<script>window.themeCartParams = '.\wp_json_encode($params).';</script>';
    }
}, 5);

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

$refresh_action = config('theme.prefix').'_refresh_cart';
add_action("wp_ajax_{$refresh_action}", $refresh_cart_handler);
add_action("wp_ajax_nopriv_{$refresh_action}", $refresh_cart_handler);

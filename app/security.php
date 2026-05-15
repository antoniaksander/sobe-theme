<?php

/**
 * Security hardening.
 */

namespace App;

// ── Head cleanup ─────────────────────────────────────────────────────────────
// Remove information-leaking tags from <head>.
remove_action('wp_head', 'wp_generator');           // WP version meta tag
remove_action('wp_head', 'rsd_link');               // Really Simple Discovery
remove_action('wp_head', 'wlwmanifest_link');       // Windows Live Writer
remove_action('wp_head', 'wp_shortlink_wp_head');   // ?p= shortlink

// ── XML-RPC ──────────────────────────────────────────────────────────────────
add_filter('xmlrpc_enabled', '__return_false');

// Prevent xmlrpc.php from being reachable at all.
add_action('init', function () {
    if (isset($_SERVER['REQUEST_URI']) && str_contains($_SERVER['REQUEST_URI'], 'xmlrpc.php')) {
        http_response_code(403);
        exit('Forbidden');
    }
});

// ── REST API access control ───────────────────────────────────────────────────
// Block unauthenticated REST access, whitelisting only the public WooCommerce
// Store API routes required for cart/checkout UX.
//
// WHY rest_pre_dispatch instead of rest_authentication_errors:
// rest_authentication_errors runs at priority 10, before WordPress validates
// the admin cookie/nonce at priority 100. is_user_logged_in() is therefore
// unreliable there — it returns false for authenticated block editor, media
// library, and Customizer requests, breaking them.
//
// rest_pre_dispatch fires after authentication is fully resolved, so
// is_user_logged_in() is accurate and wp-admin requests always pass through.
add_filter('rest_pre_dispatch', function ($result, $server, $request) {
    if (! is_null($result)) {
        return $result;
    }

    if (is_user_logged_in()) {
        return null;
    }

    $route = $request->get_route();
    $pfx = config('theme.prefix');

    $publicRoutes = apply_filters('sobe/security/public_routes', [
        'exact' => [
            '/wc/store/v1/cart',
            '/wc/store/v1/cart/add-item',
            "/{$pfx}/v1/search",
        ],
        'patterns' => [
            '#^/wc/store/v1/cart/items/[^/]+$#',
        ],
    ]);
    $publicRoutes = is_array($publicRoutes) ? $publicRoutes : [];

    if (in_array($route, $publicRoutes['exact'] ?? [], true)) {
        return null;
    }

    foreach ($publicRoutes['patterns'] ?? [] as $pattern) {
        if (is_string($pattern) && preg_match($pattern, $route) === 1) {
            return null;
        }
    }

    return new \WP_Error(
        'rest_not_logged_in',
        __('You must be logged in to access the REST API.', config('theme.textdomain')),
        ['status' => 401]
    );
}, 10, 3);

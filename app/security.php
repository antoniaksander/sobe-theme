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
// Block unauthenticated REST access, allowing through only the routes a
// logged-out visitor legitimately needs on the frontend.
//
// WHY rest_pre_dispatch instead of rest_authentication_errors:
// rest_authentication_errors runs at priority 10, before WordPress validates
// the admin cookie/nonce at priority 100. is_user_logged_in() is therefore
// unreliable there — it returns false for authenticated block editor, media
// library, and Customizer requests, breaking them.
//
// rest_pre_dispatch fires after authentication is fully resolved, so
// is_user_logged_in() is accurate and wp-admin requests always pass through.

/**
 * Default public REST route patterns, keyed off which plugins are active.
 *
 * Taken as parameters rather than read from class_exists() inside, so the
 * matrix is unit-testable without those plugins installed.
 *
 * @return array<int, string>
 */
function sobe_public_rest_route_patterns(
    bool $hasWooCommerce,
    bool $hasContactForm7,
    bool $hasYithWishlist,
): array {
    return array_values(array_filter([
        // The whole WooCommerce Store API namespace. It is designed to be
        // reachable by logged-out visitors — the cart, checkout, shipping-rate
        // and coupon routes are what the Cart and Checkout blocks call, and
        // they carry their own Cart-Token / Nonce protection.
        //
        // Allow-listing only /cart and /cart/add-item (as this did until now)
        // left the blocks-based Checkout dead for guests: /wc/store/v1/checkout,
        // /cart/update-customer, /cart/select-shipping-rate, /cart/apply-coupon
        // and /batch all 401'd. That contradicted the platform's own
        // `wp sobe migrate:checkout-page` command, whose entire job is moving a
        // client onto that Checkout block.
        $hasWooCommerce ? '#^/wc/store/v1(/|$)#' : null,

        // Contact Form 7's three genuinely public endpoints — submission,
        // its schema, and the refill used by CAPTCHA/quiz fields. All three
        // are registered by CF7 with permission_callback => __return_true.
        // Its management routes (/contact-forms, /contact-forms/{id}) are
        // capability-gated by CF7 and stay blocked here.
        //
        // Without this, every CF7 form on the site fails to submit for a
        // logged-out visitor — site-wide, not just on one page.
        $hasContactForm7
            ? '#^/contact-form-7/v1/contact-forms/\d+/(feedback(/schema)?|refill)$#'
            : null,

        // YITH WooCommerce Wishlist. Wishlists are session-based, so the
        // widget loads for guests on every page and 401s without this. YITH
        // registers its own permission callbacks on these routes, so the
        // plugin — not the theme — decides what a guest may actually do.
        $hasYithWishlist ? '#^/yith/wishlist/v1(/|$)#' : null,
    ]));
}

/**
 * Whether a resolved REST route is in the public allowlist.
 */
function sobe_rest_route_is_public(string $route, array $publicRoutes): bool
{
    if (in_array($route, $publicRoutes['exact'] ?? [], true)) {
        return true;
    }

    foreach ($publicRoutes['patterns'] ?? [] as $pattern) {
        if (is_string($pattern) && $pattern !== '' && preg_match($pattern, $route) === 1) {
            return true;
        }
    }

    return false;
}

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
            "/{$pfx}/v1/search",
        ],
        'patterns' => sobe_public_rest_route_patterns(
            class_exists('WooCommerce'),
            class_exists('WPCF7'),
            class_exists('YITH_WCWL'),
        ),
    ]);
    $publicRoutes = is_array($publicRoutes) ? $publicRoutes : [];

    if (sobe_rest_route_is_public((string) $route, $publicRoutes)) {
        return null;
    }

    return new \WP_Error(
        'rest_not_logged_in',
        __('You must be logged in to access the REST API.', config('theme.textdomain')),
        ['status' => 401]
    );
}, 10, 3);

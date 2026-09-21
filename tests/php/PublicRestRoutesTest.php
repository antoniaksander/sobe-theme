<?php

/**
 * The public REST allowlist.
 *
 * app/security.php blocks unauthenticated REST access, so anything wrongly
 * left out of this list is a 401 for every logged-out visitor, and anything
 * wrongly let in is an endpoint exposed to the internet. Both directions are
 * pinned here.
 *
 * The route-matching and pattern-building are plain functions taking the
 * plugin matrix as arguments, so this runs without WooCommerce, Contact Form 7
 * or YITH installed.
 */

use Brain\Monkey\Functions;

beforeEach(function () {
    Functions\when('add_action')->justReturn(true);
    Functions\when('add_filter')->justReturn(true);
    Functions\when('remove_action')->justReturn(true);
    Functions\when('__return_false')->justReturn(false);
    Functions\when('config')->justReturn('sobe');

    require_once dirname(__DIR__, 2).'/app/security.php';
});

function publicRoutesFor(bool $wc = true, bool $cf7 = true, bool $yith = true): array
{
    return [
        'exact' => ['/sobe/v1/search'],
        'patterns' => App\sobe_public_rest_route_patterns($wc, $cf7, $yith),
    ];
}

// ── WooCommerce Store API ────────────────────────────────────────────────────

it('allows the Store API routes the Cart and Checkout blocks call', function (string $route) {
    expect(App\sobe_rest_route_is_public($route, publicRoutesFor()))->toBeTrue();
})->with([
    '/wc/store/v1/cart',
    '/wc/store/v1/cart/add-item',
    '/wc/store/v1/cart/items/abc123',
    // Every one of these 401'd before this fix, which is what broke guest checkout.
    '/wc/store/v1/checkout',
    '/wc/store/v1/cart/update-customer',
    '/wc/store/v1/cart/select-shipping-rate',
    '/wc/store/v1/cart/apply-coupon',
    '/wc/store/v1/batch',
    '/wc/store/v1/products',
]);

it('does not allow the Store API when WooCommerce is inactive', function () {
    expect(App\sobe_rest_route_is_public('/wc/store/v1/checkout', publicRoutesFor(wc: false)))
        ->toBeFalse();
});

it('does not allow a route that merely starts with the Store API namespace string', function (string $route) {
    expect(App\sobe_rest_route_is_public($route, publicRoutesFor()))->toBeFalse();
})->with([
    '/wc/store/v1evil',
    '/wc/store/v2/cart',
    '/wc/v3/orders',
]);

// ── Contact Form 7 ───────────────────────────────────────────────────────────

it('allows only the three public Contact Form 7 endpoints', function (string $route) {
    expect(App\sobe_rest_route_is_public($route, publicRoutesFor()))->toBeTrue();
})->with([
    '/contact-form-7/v1/contact-forms/42/feedback',
    '/contact-form-7/v1/contact-forms/42/feedback/schema',
    '/contact-form-7/v1/contact-forms/42/refill',
]);

it('keeps the capability-gated Contact Form 7 management routes blocked', function (string $route) {
    expect(App\sobe_rest_route_is_public($route, publicRoutesFor()))->toBeFalse();
})->with([
    '/contact-form-7/v1/contact-forms',
    '/contact-form-7/v1/contact-forms/42',
    '/contact-form-7/v1/contact-forms/notanid/feedback',
]);

it('does not allow Contact Form 7 routes when the plugin is inactive', function () {
    expect(App\sobe_rest_route_is_public(
        '/contact-form-7/v1/contact-forms/42/feedback',
        publicRoutesFor(cf7: false),
    ))->toBeFalse();
});

// ── YITH wishlist ────────────────────────────────────────────────────────────

it('allows the YITH wishlist namespace only while the plugin is active', function () {
    expect(App\sobe_rest_route_is_public('/yith/wishlist/v1/lists', publicRoutesFor()))->toBeTrue()
        ->and(App\sobe_rest_route_is_public('/yith/wishlist/v1/lists', publicRoutesFor(yith: false)))->toBeFalse();
});

// ── Everything else stays closed ─────────────────────────────────────────────

it('keeps core and unrelated REST namespaces blocked for guests', function (string $route) {
    expect(App\sobe_rest_route_is_public($route, publicRoutesFor()))->toBeFalse();
})->with([
    '/wp/v2/users',
    '/wp/v2/posts',
    '/wp/v2/settings',
    '/wc/v3/orders',
    '/wc-admin/options',
    '/',
]);

it('allows the theme search endpoint by exact match only', function () {
    expect(App\sobe_rest_route_is_public('/sobe/v1/search', publicRoutesFor()))->toBeTrue()
        ->and(App\sobe_rest_route_is_public('/sobe/v1/search/all', publicRoutesFor()))->toBeFalse();
});

it('ignores empty or non-string patterns supplied through the filter', function () {
    $routes = ['exact' => [], 'patterns' => ['', null, 42, '#^/ok$#']];

    expect(App\sobe_rest_route_is_public('/ok', $routes))->toBeTrue()
        ->and(App\sobe_rest_route_is_public('/nope', $routes))->toBeFalse();
});

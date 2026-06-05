<?php

/**
 * Unit tests for the legacy cart-page migration guard.
 *
 * The guard is the security-sensitive part: it must recognise ONLY exact
 * legacy shortcode forms so a customised cart page is never clobbered.
 * The migration functions live in a runtime-loaded file (not PSR-4), so we
 * require it directly.
 */

require_once dirname(__DIR__, 2) . '/app/cart-migration.php';

it('normalises whitespace for comparison', function () {
    expect(\App\sobe_normalize_cart_content("  [woocommerce_cart]\n\n "))->toBe('[woocommerce_cart]');
});

it('recognises the bare legacy shortcode', function () {
    expect(\App\sobe_is_legacy_cart_content('[woocommerce_cart]'))->toBeTrue();
});

it('recognises the legacy shortcode wrapped in a shortcode block', function () {
    expect(\App\sobe_is_legacy_cart_content('<!-- wp:shortcode -->[woocommerce_cart]<!-- /wp:shortcode -->'))->toBeTrue();
    // Tolerant of the newlines WordPress inserts around the shortcode block.
    expect(\App\sobe_is_legacy_cart_content("<!-- wp:shortcode -->\n[woocommerce_cart]\n<!-- /wp:shortcode -->"))->toBeTrue();
});

it('does NOT migrate a customised or non-legacy cart page', function () {
    expect(\App\sobe_is_legacy_cart_content('<!-- wp:woocommerce/cart /-->'))->toBeFalse();
    expect(\App\sobe_is_legacy_cart_content('[woocommerce_cart] <p>Need help? Call us.</p>'))->toBeFalse();
    expect(\App\sobe_is_legacy_cart_content('[woocommerce_checkout]'))->toBeFalse();
    expect(\App\sobe_is_legacy_cart_content(''))->toBeFalse();
});

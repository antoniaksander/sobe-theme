<?php

/**
 * Unit tests for the legacy checkout-page migration guard.
 *
 * The guard is the security-sensitive part: it must recognise ONLY exact
 * legacy shortcode forms so a customised checkout page is never clobbered.
 * The migration functions live in a runtime-loaded file (not PSR-4), so we
 * require it directly.
 */

require_once dirname(__DIR__, 2) . '/app/checkout-migration.php';

it('normalises whitespace for comparison', function () {
    expect(\App\sobe_normalize_checkout_content("  [woocommerce_checkout]\n\n "))->toBe('[woocommerce_checkout]');
});

it('recognises the bare legacy shortcode', function () {
    expect(\App\sobe_is_legacy_checkout_content('[woocommerce_checkout]'))->toBeTrue();
});

it('recognises the legacy shortcode wrapped in a shortcode block', function () {
    expect(\App\sobe_is_legacy_checkout_content('<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->'))->toBeTrue();
    // Tolerant of the newlines WordPress inserts around the shortcode block.
    expect(\App\sobe_is_legacy_checkout_content("<!-- wp:shortcode -->\n[woocommerce_checkout]\n<!-- /wp:shortcode -->"))->toBeTrue();
});

it('does NOT migrate a customised or non-legacy checkout page', function () {
    expect(\App\sobe_is_legacy_checkout_content('<!-- wp:woocommerce/checkout /-->'))->toBeFalse();
    expect(\App\sobe_is_legacy_checkout_content('[woocommerce_checkout] <p>Need help? Call us.</p>'))->toBeFalse();
    expect(\App\sobe_is_legacy_checkout_content('[woocommerce_cart]'))->toBeFalse();
    expect(\App\sobe_is_legacy_checkout_content(''))->toBeFalse();
});

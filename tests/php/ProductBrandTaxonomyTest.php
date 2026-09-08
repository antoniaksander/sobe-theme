<?php

/**
 * Regression tests for the product_brand taxonomy registration guard.
 *
 * WooCommerce's native Brands feature (default-on since WooCommerce 9.6,
 * available behind a feature flag from 9.4) registers a taxonomy named
 * `product_brand` on `init` priority 5 — before Sobe's own `init` (default
 * priority 10) callback runs. These tests cover the ownership handoff:
 * Sobe must defer to an existing `product_brand` registration rather than
 * silently overwrite it, but must still provide the taxonomy itself when
 * nothing else has claimed the name.
 */

use Brain\Monkey\Functions;

// setup-patterns.php is a runtime-loaded file (not PSR-4), so we require it
// directly, matching CartMigrationTest.php / CheckoutMigrationTest.php.
// Unlike those files, this one calls add_action()/add_shortcode() (and one
// eagerly-evaluated config() call) at the top level, so those must be
// mocked BEFORE the require — which means the require has to happen inside
// beforeEach, after Brain Monkey's setUp() has patched the global function
// table, not at file-parse time. require_once is idempotent: the file body
// (and its add_action calls) only actually executes on the first test.
beforeEach(function () {
    Functions\when('__')->returnArg();
    Functions\when('add_action')->justReturn(true);
    Functions\when('add_shortcode')->justReturn(true);
    Functions\when('config')->justReturn('sobe');

    require_once dirname(__DIR__, 2).'/app/setup-patterns.php';
});

it('does not re-register product_brand when another owner (native WooCommerce Brands) already has', function () {
    Functions\when('apply_filters')->alias(fn ($hook, $value = null) => $value);
    Functions\when('taxonomy_exists')->justReturn(true);
    Functions\expect('register_taxonomy')->never();

    expect(App\sobe_register_product_brand_taxonomy())->toBeNull();
});

it('registers product_brand as a fallback when no owner has registered it', function () {
    Functions\when('apply_filters')->alias(fn ($hook, $value = null) => $value);
    Functions\when('taxonomy_exists')->justReturn(false);

    Functions\expect('register_taxonomy')
        ->once()
        ->with('product_brand', 'product', Mockery::on(function (array $args): bool {
            expect($args['hierarchical'])->toBeFalse();
            expect($args['show_in_rest'])->toBeTrue();
            expect($args['public'])->toBeTrue();
            expect($args['rewrite'])->toBe(['slug' => 'brand']);

            return true;
        }));

    App\sobe_register_product_brand_taxonomy();
});

it('checks taxonomy_exists only after the opt-out filter has been consulted', function () {
    $calls = [];

    Functions\when('apply_filters')->alias(function ($hook, $value = null) use (&$calls) {
        $calls[] = 'apply_filters:'.$hook;

        return $value;
    });
    Functions\when('taxonomy_exists')->alias(function () use (&$calls) {
        $calls[] = 'taxonomy_exists';

        return false;
    });
    Functions\when('register_taxonomy')->justReturn(true);

    App\sobe_register_product_brand_taxonomy();

    expect($calls)->toBe(['apply_filters:sobe/product_brand/register', 'taxonomy_exists']);
});

it('still honours the existing sobe/product_brand/register opt-out filter, regardless of taxonomy state', function () {
    Functions\when('apply_filters')->alias(fn ($hook, $value = null) => $hook === 'sobe/product_brand/register' ? false : $value);
    Functions\when('taxonomy_exists')->justReturn(false);
    Functions\expect('register_taxonomy')->never();

    expect(App\sobe_register_product_brand_taxonomy())->toBeNull();
});

it('honours the opt-out filter even when no other owner has registered product_brand', function () {
    Functions\when('apply_filters')->alias(fn ($hook, $value = null) => $hook === 'sobe/product_brand/register' ? false : $value);
    Functions\when('taxonomy_exists')->justReturn(true);
    Functions\expect('register_taxonomy')->never();

    expect(App\sobe_register_product_brand_taxonomy())->toBeNull();
});

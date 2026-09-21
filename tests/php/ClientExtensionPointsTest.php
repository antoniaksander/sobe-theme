<?php

/**
 * Tests for the config-driven client extension points.
 *
 * A fork used to register its own PHP modules by editing the module list in
 * functions.php, and its own editor block categories by editing the array in
 * app/blocks.php. Both are platform-owned files, so both edits conflicted on
 * every upstream sync — for every client, forever. These tests pin the
 * config-driven replacements so that contract does not regress.
 */

use Brain\Monkey\Functions;

/**
 * app/blocks.php registers its hooks at the top level, so the callback under
 * test has to be captured as the file is required. Brain Monkey's function
 * table is only patched inside a test lifecycle, hence the require here rather
 * than at file-parse time.
 */
function captureBlockCategoriesCallback(array $clientCategories): callable
{
    $captured = null;

    Functions\when('add_action')->justReturn(true);
    Functions\when('__')->returnArg();
    Functions\when('config')->alias(function (string $key, $default = null) use ($clientCategories) {
        return match ($key) {
            'theme.block_categories' => $clientCategories,
            'theme.textdomain' => 'sobe',
            'theme.prefix' => 'sobe',
            default => $default,
        };
    });

    Functions\when('add_filter')->alias(function ($hook, $callback) use (&$captured) {
        if ($hook === 'block_categories_all') {
            $captured = $callback;
        }

        return true;
    });

    require dirname(__DIR__, 2).'/app/blocks.php';

    return $captured;
}

/**
 * app/blocks.php only registers `sobe-woocommerce` when WooCommerce is loaded,
 * and the php-stubs/woocommerce-stubs dev dependency makes that class
 * resolvable in the test process. Derive the expectation rather than hardcode
 * it — that gating is covered by the block registration guard, not here.
 */
function expectedPlatformSlugs(): array
{
    return array_values(array_filter([
        'sobe-general',
        class_exists('WooCommerce') ? 'sobe-woocommerce' : null,
        'sobe-content',
        'sobe-layout',
    ]));
}

/**
 * Each test needs app/blocks.php evaluated again with different config, and
 * require_once would only run the file body once for the whole suite. The file
 * declares no functions or classes, so a plain require is safe to repeat.
 */
it('prepends client block categories ahead of the platform ones', function () {
    $callback = captureBlockCategoriesCallback([
        ['slug' => 'mezza', 'title' => 'Studio Mezza', 'icon' => 'admin-multisite'],
    ]);

    $result = $callback([['slug' => 'core-widgets', 'title' => 'Widgets', 'icon' => null]]);

    $slugs = array_column($result, 'slug');

    // Client first, so a fork's own blocks sit at the top of the inserter.
    expect($slugs[0])->toBe('mezza')
        // Platform categories still registered, in their existing order.
        ->and($slugs)->toContain('sobe-general', 'sobe-content', 'sobe-layout')
        // WordPress's own categories are passed through untouched.
        ->and($slugs)->toContain('core-widgets');
});

it('registers the platform categories unchanged when a fork declares none', function () {
    $callback = captureBlockCategoriesCallback([]);

    $slugs = array_column($callback([]), 'slug');

    expect($slugs)->toBe(expectedPlatformSlugs());
});

it('ignores malformed client category entries rather than emitting broken ones', function () {
    $callback = captureBlockCategoriesCallback([
        'not-an-array',
        ['title' => 'Missing its slug'],
        ['slug' => 'valid', 'title' => 'Valid', 'icon' => 'layout'],
    ]);

    $slugs = array_column($callback([]), 'slug');

    expect($slugs)->toBe(array_merge(['valid'], expectedPlatformSlugs()));
});

it('declares every client extension point in config/theme.php, defaulting to empty', function () {
    $config = require dirname(__DIR__, 2).'/config/theme.php';

    foreach (['client_modules', 'disabled_modules', 'block_categories'] as $key) {
        expect($config)->toHaveKey($key)
            ->and($config[$key])->toBeArray()
            // The platform ships these inert; a non-empty default would mean
            // the platform had started using a client-owned extension point.
            ->and($config[$key])->toBeEmpty();
    }
});

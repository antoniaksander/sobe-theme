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

it('declares every client extension point in config/theme.php', function () {
    $config = require dirname(__DIR__, 2).'/config/theme.php';

    foreach (['client_modules', 'disabled_modules', 'block_categories', 'client_header_layouts', 'client_footer_layouts'] as $key) {
        expect($config)->toHaveKey($key)->and($config[$key])->toBeArray();
    }

    foreach (['default_header_layout', 'default_footer_layout'] as $key) {
        expect($config)->toHaveKey($key)->and($config[$key])->toBeString()->not->toBeEmpty();
    }
});

/**
 * app/helpers.php only declares functions, so it is safe to load once here.
 */
function mockLayoutConfig(array $overrides = []): void
{
    require_once dirname(__DIR__, 2).'/app/helpers.php';

    Functions\when('__')->returnArg();
    Functions\when('config')->alias(function (string $key, $default = null) use ($overrides) {
        return array_key_exists($key, $overrides) ? $overrides[$key] : match ($key) {
            'theme.prefix' => 'sobe',
            default => $default,
        };
    });
}

it('lists client layouts first and marks the configured default', function () {
    mockLayoutConfig([
        'theme.client_header_layouts' => ['header-client' => 'Client header'],
        'theme.default_header_layout' => 'header-client',
    ]);

    $choices = \App\sobe_layout_choices('header');

    expect(array_keys($choices))->toBe(['header-client', 'header-1', 'header-2', 'header-3'])
        ->and($choices['header-client'])->toBe('Client header (Default)')
        ->and(\App\sobe_default_layout('header'))->toBe('header-client');
});

it('keeps the platform layouts and defaults when a fork declares none', function () {
    mockLayoutConfig();

    expect(array_keys(\App\sobe_layout_choices('footer')))->toBe(['layout-2', 'none'])
        ->and(\App\sobe_default_layout('header'))->toBe('header-1')
        ->and(\App\sobe_default_layout('footer'))->toBe('layout-2');
});

it('falls back to the default for a stored layout that no longer exists', function () {
    mockLayoutConfig(['theme.client_footer_layouts' => ['footer-client' => 'Client footer']]);

    Functions\when('get_theme_mod')->justReturn('removed-layout');
    expect(\App\sobe_current_layout('footer'))->toBe('layout-2');

    Functions\when('get_theme_mod')->justReturn('footer-client');
    expect(\App\sobe_current_layout('footer'))->toBe('footer-client');
});

/**
 * Runs in forks too, where it is the assertion that earns its keep: a typo in
 * a fork's module list would otherwise surface as a wp_die() at boot rather
 * than a failing test. The platform's own lists are empty, so this passes
 * vacuously upstream.
 */
it('resolves every declared client module to a file that exists', function () {
    $root = dirname(__DIR__, 2);
    $config = require $root.'/config/theme.php';

    expect($config['client_modules'])->toBeArray();

    foreach ($config['client_modules'] as $module) {
        expect($module)->toBeString()->not->toBeEmpty()
            ->and($root.'/app/'.$module.'.php')->toBeFile();
    }
});

it('declares well-formed disabled modules and block categories', function () {
    $config = require dirname(__DIR__, 2).'/config/theme.php';

    expect($config['disabled_modules'])->toBeArray()
        ->and($config['block_categories'])->toBeArray();

    foreach ($config['disabled_modules'] as $module) {
        expect($module)->toBeString()->not->toBeEmpty();
    }

    foreach ($config['block_categories'] as $category) {
        expect($category)->toBeArray()
            ->and($category)->toHaveKeys(['slug', 'title'])
            ->and($category['slug'])->toBeString()->not->toBeEmpty();
    }
});

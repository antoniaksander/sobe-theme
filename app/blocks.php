<?php

/**
 * Block registration infrastructure.
 */

namespace App;

use function Roots\view;

add_action('init', function (): void {
    $pfx = config('theme.prefix');
    $manifestPath = resource_path('blocks/blocks-manifest.json');
    $manifest = is_readable($manifestPath)
        ? json_decode(file_get_contents($manifestPath), true)
        : [];

    foreach (array_keys($manifest ?: []) as $blockSlug) {
        $assetUri = \Roots\asset("resources/blocks/{$blockSlug}/index.jsx")->uri();

        wp_register_script(
            "{$pfx}-{$blockSlug}",
            $assetUri,
            ['wp-blocks', 'wp-element', 'wp-components', 'wp-i18n', 'wp-block-editor'],
            null,
            true
        );

        $blockArgs = [
            'editor_script' => "{$pfx}-{$blockSlug}",
            'render_callback' => function ($attributes, $content = '') use ($blockSlug) {
                return view("blocks.{$blockSlug}", compact('attributes', 'content'))->render();
            },
        ];

        $stylePath = resource_path("blocks/{$blockSlug}/style.scss");
        if (file_exists($stylePath)) {
            wp_register_style(
                "{$pfx}-{$blockSlug}-style",
                \Roots\asset("resources/blocks/{$blockSlug}/style.scss")->uri(),
                [],
                null
            );
            $blockArgs['style'] = "{$pfx}-{$blockSlug}-style";
        }

        $editorStylePath = resource_path("blocks/{$blockSlug}/editor.scss");
        if (file_exists($editorStylePath)) {
            wp_register_style(
                "{$pfx}-{$blockSlug}-editor-style",
                \Roots\asset("resources/blocks/{$blockSlug}/editor.scss")->uri(),
                [],
                null
            );
            $blockArgs['editor_style'] = "{$pfx}-{$blockSlug}-editor-style";
        }

        $viewPath = resource_path("blocks/{$blockSlug}/view.js");
        if (file_exists($viewPath)) {
            wp_register_script(
                "{$pfx}-{$blockSlug}-view",
                \Roots\asset("resources/blocks/{$blockSlug}/view.js")->uri(),
                [],
                null,
                true
            );
            $blockArgs['view_script'] = "{$pfx}-{$blockSlug}-view";
        }

        register_block_type(resource_path("blocks/{$blockSlug}"), $blockArgs);
    }
});

add_filter('script_loader_tag', function ($tag, $handle) {
    $pfx = config('theme.prefix');

    if (str_starts_with($handle, "{$pfx}-")) {
        return str_replace(' src=', ' type="module" src=', $tag);
    }

    return $tag;
}, 10, 2);

add_filter('allowed_block_types_all', function ($allowedBlocks) {
    $configPath = resource_path('config/core-allowed-blocks.json');
    $config = is_readable($configPath)
        ? json_decode(file_get_contents($configPath), true)
        : [];

    $allowed = array_merge($config['core'] ?? [], $config['woocommerce'] ?? []);

    $pfx = config('theme.prefix');
    foreach (\WP_Block_Type_Registry::get_instance()->get_all_registered() as $name => $block) {
        if (str_starts_with($name, 'sobe/') || str_starts_with($name, "{$pfx}/")) {
            $allowed[] = $name;
        }
    }

    return apply_filters(
        'sobe/blocks/allowed_types',
        array_values(array_unique($allowed)),
        $allowedBlocks
    );
});

add_filter('block_categories_all', function ($categories) {
    return array_merge([
        ['slug' => 'sobe-general', 'title' => __('Sobe General', config('theme.textdomain')), 'icon' => 'layout'],
        ['slug' => 'sobe-woocommerce', 'title' => __('Sobe WooCommerce', config('theme.textdomain')), 'icon' => 'cart'],
        ['slug' => 'sobe-content', 'title' => __('Sobe Content', config('theme.textdomain')), 'icon' => 'text'],
        ['slug' => 'sobe-layout', 'title' => __('Sobe Layout', config('theme.textdomain')), 'icon' => 'layout'],
    ], $categories);
});

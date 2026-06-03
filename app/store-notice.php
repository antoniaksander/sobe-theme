<?php

declare(strict_types=1);

namespace App;

// Suppress WooCommerce's native .demo_store notice — the theme renders its own.
add_action('after_setup_theme', function (): void {
    remove_action('wp_footer', 'woocommerce_demo_store');
});

/**
 * Returns the announcement bar messages as an array of strings.
 *
 * Primary source: Customizer → WooCommerce → Store Notice (woocommerce_demo_store_notice).
 * The notice is only used when "Enable store notice" is ticked in the Customizer.
 * Pipe-separated text becomes a multi-message slideshow.
 *
 * Fallback for non-WooCommerce sites: config('theme.announcement_bar').
 *
 * @return string[]
 */
function sobe_announcement_bar_messages(): array
{
    // WooCommerce Customizer → Store Notice is the primary source.
    if (class_exists('WooCommerce') && get_option('woocommerce_demo_store') === 'yes') {
        $raw = (string) get_option('woocommerce_demo_store_notice', '');
        if ($raw !== '') {
            return array_values(array_filter(array_map('trim', explode('|', $raw))));
        }
    }

    // Fallback for sites without WooCommerce.
    if (! (bool) config('theme.announcement_bar.enabled', false)) {
        return [];
    }

    $raw = (string) config('theme.announcement_bar.messages', '');

    if ($raw === '') {
        return [];
    }

    return array_values(array_filter(array_map('trim', explode('|', $raw))));
}

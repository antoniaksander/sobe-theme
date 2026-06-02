<?php

/**
 * Theme appearance runtime configuration.
 *
 * Emits window.sobeThemeConfig at wp_head priority 4 so the JS boot
 * can read it before any enqueued scripts run.
 */

namespace App;

add_action('wp_head', function (): void {
    $defaultMode = (string) config('theme.color_mode.default', 'light');
    $allowedModes = ['light', 'dark', 'system'];

    if (! in_array($defaultMode, $allowedModes, true)) {
        $defaultMode = 'light';
    }

    $pfx = config('theme.prefix');
    $params = [
        'defaultColorMode'      => $defaultMode,
        'darkModeToggleEnabled' => (bool) get_theme_mod("{$pfx}_enable_dark_toggle", false),
    ];

    echo '<script>window.sobeThemeConfig = ' . wp_json_encode($params, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';</script>';
}, 4);

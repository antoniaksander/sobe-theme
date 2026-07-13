<?php

/**
 * Theme appearance runtime configuration.
 *
 * Emits window.sobeThemeConfig at wp_head priority 4 so the JS boot
 * can read it before any enqueued scripts run.
 *
 * Also applies the dark-mode class here, synchronously, in the same
 * inline <script> tag -- this same class-detection logic used to live
 * only in the main app.js bundle, which is loaded as an ES module (always
 * deferred per spec, executing only after the document has fully parsed).
 * On a visitor with dark mode already selected, that meant every full
 * page navigation painted the page once in light mode and only then
 * flipped to dark once the deferred bundle finally ran. A plain,
 * non-module, blocking inline script tag executes immediately as the
 * parser reaches it, before first paint -- the standard fix for this
 * exact class of flash-of-wrong-theme bug.
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

    echo '<script>'
        .'window.sobeThemeConfig = ' . wp_json_encode($params, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';'
        .'(function(c){'
        .'var allowed=["light","dark","system"];'
        .'var mode=allowed.indexOf(c.defaultColorMode)!==-1?c.defaultColorMode:"light";'
        .'var stored=c.darkModeToggleEnabled===true?localStorage.getItem("theme"):null;'
        .'var prefersDark=window.matchMedia("(prefers-color-scheme: dark)").matches;'
        .'var defaultPrefersDark=mode==="dark"||(mode==="system"&&prefersDark);'
        .'if(stored==="dark"||(!stored&&defaultPrefersDark)){document.documentElement.classList.add("dark");}'
        .'})(window.sobeThemeConfig);'
        .'</script>';
}, 4);

<?php

/**
 * Theme configuration — the primary control panel for client forks.
 *
 * Change `prefix` first when forking. Everything that uses a `sobe_` key in PHP
 * and a `sobe-` handle in JS reads from here. i18n textdomain strings in __()
 * calls must stay literal (`'sobe'`) — they cannot be dynamic.
 *
 * Also update:
 *   style.css               — Theme Name, Text Domain
 *   resources/blocks/ * /block.json — "name" and "category" (static JSON, manual)
 *   resources/css/tokens.css — brand colours, fonts
 *   resources/scripts/build-theme-json.js — editorFonts array
 */

return [
    'prefix' => 'sobe',

    'excerpt_length' => 15,

    'image_sizes' => [
        'hero' => [1920, 1080, true],
    ],

    'wc_columns' => [
        'mobile' => 1,
        'tablet' => 3,
        'desktop' => 3,
    ],

    // Aspect ratio for the single-product Swiper gallery container.
    // Must match the woocommerce_single image crop dimensions set in
    // WooCommerce → Settings → Products → Product images.
    // Common values: '1 / 1' (square), '4 / 5' (portrait), '3 / 4' (portrait).
    'wc_gallery_aspect_ratio' => '1 / 1',
];

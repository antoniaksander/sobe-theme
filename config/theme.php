<?php

/**
 * Theme configuration — the primary control panel for client forks.
 *
 * The thin boilerplate keeps the Sobe namespace as its infrastructure signature.
 * Client presentation lives in demo/client repositories.
 */

return [
    'prefix' => 'sobe',

    'textdomain' => 'sobe',

    'excerpt_length' => 15,

    /*
     * Client-owned PHP modules, loaded from app/ after every platform module.
     *
     * Entries are paths relative to app/ without the .php extension, so both
     * 'mezza-gallery' (app/mezza-gallery.php) and 'client/gallery'
     * (app/client/gallery.php) resolve. Declaring them here means a fork never
     * edits functions.php to register a module, which was previously a
     * guaranteed merge conflict on every upstream sync.
     */
    'client_modules' => [],

    /*
     * Platform modules this fork replaces outright, same path form as above.
     *
     * Use this only when a fork ships its own implementation of a platform
     * module; prefer the documented hooks over dropping a platform module.
     */
    'disabled_modules' => [],

    /*
     * Client-owned editor block categories, prepended to the platform's so a
     * fork's own blocks sit at the top of the inserter. Each entry is a
     * ['slug' => ..., 'title' => ..., 'icon' => ...] array, same shape
     * WordPress expects from `block_categories_all`.
     *
     * Titles are used verbatim and are not passed through the translation
     * extractor — a fork that needs translated category titles should add
     * them with its own `block_categories_all` filter from a client module
     * instead.
     */
    'block_categories' => [],

    'color_mode' => [
        // Allowed values: 'light', 'dark', or 'system' (follows OS preference).
        // Used as the site default when the dark-mode toggle is disabled,
        // and as the first-visit fallback when the toggle is enabled.
        'default' => 'light',
    ],

    'image_sizes' => [
        'hero' => [1920, 1080, true],
    ],

    'page_transitions' => [
        'enabled' => false,
        'container_selector' => '#main',
    ],

    'blocks' => [
        // Keep the scaffold block available for platform development without
        // registering it in production/client editors by default.
        'register_example' => false,
    ],

    'product_catalog' => [
        'mobile_columns' => 1,
        'tablet_columns' => 3,
        'desktop_columns' => 3,
        'per_page' => 12,
    ],

    // Aspect ratio for the single-product Swiper gallery container.
    // Must match the woocommerce_single image crop dimensions set in
    // WooCommerce → Settings → Products → Product images.
    // Common values: '1 / 1' (square), '4 / 5' (portrait), '3 / 4' (portrait).
    'wc_gallery_aspect_ratio' => '1 / 1',

    'announcement_bar' => [
        // WooCommerce sites: the bar is driven by Customizer → WooCommerce → Store Notice.
        //   Enable/disable via the "Enable store notice" toggle there.
        //   Pipe-separated text in the notice field creates a multi-message slideshow.
        // Non-WooCommerce sites: set enabled => true and add messages here instead.
        'enabled'  => false,
        'messages' => '', // e.g. 'Free shipping over $50 | Summer sale on now'
    ],
];

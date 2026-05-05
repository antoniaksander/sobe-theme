<?php

/**
 * Theme setup.
 */

namespace App;

use Illuminate\Support\Facades\Vite;

use function Roots\view;

/**
 * Register custom blocks explicitly.
 *
 * Add the slug of each new block to $custom_blocks. No filesystem scanning.
 */
add_action('init', function () {
    $pfx = config('theme.prefix');
    $manifest_path = resource_path('blocks/blocks-manifest.json');
    $custom_blocks = is_readable($manifest_path)
        ? array_keys(json_decode(file_get_contents($manifest_path), true) ?? [])
        : [];

    foreach ($custom_blocks as $block_slug) {
        $asset_uri = \Roots\asset('resources/blocks/' . $block_slug . '/index.jsx')->uri();

        wp_register_script(
            "{$pfx}-{$block_slug}",
            $asset_uri,
            ['wp-blocks', 'wp-element', 'wp-components', 'wp-i18n', 'wp-block-editor'],
            null,
            true
        );

        $block_args = [
            'editor_script'   => "{$pfx}-{$block_slug}",
            'render_callback' => function ($attributes, $content = '') use ($block_slug) {
                return view('blocks.' . $block_slug, compact('attributes', 'content'))->render();
            },
        ];

        // Register style.scss when it exists — loads on frontend for any page using the block.
        // Same pattern as view.js: Vite hashes the filename, \Roots\asset() resolves via manifest.
        $style_path = resource_path('blocks/' . $block_slug . '/style.scss');
        if (file_exists($style_path)) {
            $style_uri = \Roots\asset('resources/blocks/' . $block_slug . '/style.scss')->uri();
            wp_register_style("{$pfx}-{$block_slug}-style", $style_uri, [], null);
            $block_args['style'] = "{$pfx}-{$block_slug}-style";
        }

        // Register editor.scss when it exists — loads only in the block editor.
        $editor_style_path = resource_path('blocks/' . $block_slug . '/editor.scss');
        if (file_exists($editor_style_path)) {
            $editor_style_uri = \Roots\asset('resources/blocks/' . $block_slug . '/editor.scss')->uri();
            wp_register_style("{$pfx}-{$block_slug}-editor-style", $editor_style_uri, [], null);
            $block_args['editor_style'] = "{$pfx}-{$block_slug}-editor-style";
        }

        // Register view.js when it exists — used for blocks with frontend JS (e.g. hero WebGL).
        // Not registered via block.json viewScript — Vite hashes filenames; \Roots\asset() reads
        // the manifest and resolves the correct URL.
        $view_path = resource_path('blocks/' . $block_slug . '/view.js');
        if (file_exists($view_path)) {
            $view_uri = \Roots\asset('resources/blocks/' . $block_slug . '/view.js')->uri();
            wp_register_script("{$pfx}-{$block_slug}-view", $view_uri, [], null, true);
            $block_args['view_script'] = "{$pfx}-{$block_slug}-view";
        }

        register_block_type(resource_path('blocks/' . $block_slug), $block_args);
    }
});

/**
 * Register block pattern category and patterns.
 */
add_action('init', function () {
    $pfx = config('theme.prefix');

    register_block_pattern_category('sobe-patterns', [
        'label' => __('Sobe Layouts', 'sage'),
    ]);

    register_block_pattern('sobe/homepage-showcase', [
        'title'       => __('Homepage Showcase', 'sage'),
        'description' => __('High-end agency homepage with hero, brand carousel, and product features.', 'sage'),
        'categories'  => ['sobe-patterns'],
        'content'     => require resource_path('patterns/homepage-showcase.php'),
    ]);
});

// Isolate module scope for each block to prevent Vite minifier collisions with window._
add_filter('script_loader_tag', function ($tag, $handle) {
    $pfx = config('theme.prefix');
    if (str_starts_with($handle, "{$pfx}-")) {
        return str_replace(' src=', ' type="module" src=', $tag);
    }
    return $tag;
}, 10, 2);

/**
 * Register product_brand taxonomy.
 */
add_action('init', function () {
    register_taxonomy('product_brand', 'product', [
        'label' => __('Brands', 'sobe'),
        'labels' => [
            'name' => __('Brands', 'sobe'),
            'singular_name' => __('Brand', 'sobe'),
            'add_new_item' => __('Add New Brand', 'sobe'),
            'edit_item' => __('Edit Brand', 'sobe'),
        ],
        'public' => true,
        'hierarchical' => false,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'brand'],
    ]);
});

// Restrict the block inserter to a curated set — prevents clients from breaking
// the design system with layout blocks like core/columns or core/cover.
add_filter('allowed_block_types_all', function ($allowed_blocks, $editor_context) {
    $allowed = [
        'core/paragraph',
        'core/heading',
        'core/list',
        'core/list-item',
        'core/image',
        'core/quote',
        // 'core/embed',
        'core/button',
        'core/buttons',
        'core/separator',
        'core/spacer',
        'core/shortcode',
        'core/table',
        'core/group',
        'core/columns',
        'core/column',
        // Current WooCommerce Product Filter Block
        'woocommerce/product-filters',
        'woocommerce/active-filters', // Only use if explicitly supported by your version
        // Common Product Display Blocks
        'woocommerce/all-products',
        'woocommerce/product-search',
        'woocommerce/handpicked-products',
    ];

    $pfx = config('theme.prefix');
    $registered_blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();

    foreach ($registered_blocks as $name => $block) {
        if (strpos($name, "{$pfx}/") === 0) {
            $allowed[] = $name;
        }
    }

    return $allowed;
}, 10, 2);

// When adding a category here, also update VALID_CATEGORIES in resources/scripts/make-block.js.
add_filter('block_categories_all', function ($categories) {
    $custom = [
        ['slug' => 'sobe-general',     'title' => __('Sobe General', 'sobe'),     'icon' => 'layout'],
        ['slug' => 'sobe-woocommerce', 'title' => __('Sobe WooCommerce', 'sobe'), 'icon' => 'cart'],
        ['slug' => 'sobe-sliders',     'title' => __('Sobe Sliders', 'sobe'),     'icon' => 'slides'],
        ['slug' => 'sobe-content',     'title' => __('Sobe Content', 'sobe'),     'icon' => 'text'],
    ];
    return array_merge($custom, $categories);
});

add_action('enqueue_block_editor_assets', function () {
    wp_enqueue_media();
});

/**
 * Inject styles into the block editor.
 *
 * @return array
 */
add_filter('block_editor_settings_all', function ($settings) {
    $style = Vite::asset('resources/css/editor.css');
    $themeUrl = get_stylesheet_directory_uri();

    $satoshiUrl = $themeUrl.'/fonts/Satoshi-Variable.woff2';
    $satoshiFace = "@font-face{font-family:'Satoshi';src:url('{$satoshiUrl}')format('woff2');font-weight:300 900;font-display:swap;font-style:normal}";

    $cabinetUrl = $themeUrl.'/fonts/CabinetGrotesk-Variable.woff2';
    $cabinetFace = "@font-face{font-family:'CabinetGrotesk';src:url('{$cabinetUrl}')format('woff2');font-weight:100 900;font-display:swap;font-style:normal}";

    $settings['styles'][] = [
        'css' => $satoshiFace.$cabinetFace,
    ];
    $settings['styles'][] = [
        'css' => "@import url('{$style}')",
    ];

    return $settings;
});

/**
 * Inject scripts into the block editor.
 *
 * @return void
 */
add_action('admin_head', function () {
    if (! get_current_screen()?->is_block_editor()) {
        return;
    }

    if (! Vite::isRunningHot()) {
        $dependencies = json_decode(Vite::content('editor.deps.json'));

        foreach ($dependencies as $dependency) {
            if (! wp_script_is($dependency)) {
                wp_enqueue_script($dependency);
            }
        }
    }
    echo Vite::withEntryPoints([
        'resources/js/editor.js',
    ])->toHtml();
});

/**
 * Use the generated theme.json file.
 *
 * @return string
 */
add_filter('theme_file_path', function ($path, $file) {
    return $file === 'theme.json'
        ? public_path('build/assets/theme.json')
        : $path;
}, 10, 2);

/**
 * Enqueue fonts in head for priority loading.
 *
 * @return void
 */
add_action('wp_head', function () {
    $themeUrl = get_stylesheet_directory_uri();
    $satoshiUrl = $themeUrl.'/fonts/Satoshi-Variable.woff2';
    $satoshiFace = "@font-face{font-family:'Satoshi';src:url('{$satoshiUrl}')format('woff2');font-weight:300 900;font-display:swap;font-style:normal}";

    $cabinetUrl = $themeUrl.'/fonts/CabinetGrotesk-Variable.woff2';
    $cabinetFace = "@font-face{font-family:'CabinetGrotesk';src:url('{$cabinetUrl}')format('woff2');font-weight:100 900;font-display:swap;font-style:normal}";

    echo '<style>'.$satoshiFace.$cabinetFace.'</style>';
}, 1);

/**
 * Disable on-demand block asset loading.
 *
 * @link https://core.trac.wordpress.org/ticket/61965
 */
add_filter('should_load_separate_core_block_assets', '__return_false');

/**
 * Register the initial theme setup.
 *
 * @return void
 */
add_action('after_setup_theme', function () {
    /**
     * Disable full-site editing support.
     *
     * @link https://wptavern.com/gutenberg-10-5-embeds-pdfs-adds-verse-block-color-options-and-introduces-new-patterns
     */
    remove_theme_support('block-templates');

    /**
     * Register the navigation menus.
     *
     * @link https://developer.wordpress.org/reference/functions/register_nav_menus/
     */
    register_nav_menus([
        'primary_navigation' => __('Primary Navigation', 'sobe'),
        'footer_navigation'  => __('Footer Navigation', 'sobe'),
    ]);

    /**
     * Disable the default block patterns.
     *
     * @link https://developer.wordpress.org/block-editor/developers/themes/theme-support/#disabling-the-default-block-patterns
     */
    remove_theme_support('core-block-patterns');

    /**
     * Enable plugins to manage the document title.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#title-tag
     */
    add_theme_support('title-tag');

    /**
     * Enable post thumbnail support.
     *
     * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
     */
    add_theme_support('post-thumbnails');

    $pfx = config('theme.prefix');
    foreach (config('theme.image_sizes') as $name => $args) {
        add_image_size("{$pfx}-{$name}", ...$args);
    }

    /**
     * Enable responsive embed support.
     *
     * @link https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#responsive-embedded-content
     */
    add_theme_support('align-wide');

    add_theme_support('responsive-embeds');

    /**
     * Enable automatic feed links in <head>.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#automatic-feed-links
     */
    add_theme_support('automatic-feed-links');

    /**
     * Enable HTML5 markup support.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#html5
     */
    add_theme_support('html5', [
        'caption',
        'comment-form',
        'comment-list',
        'gallery',
        'search-form',
        'script',
        'style',
    ]);

    /**
     * Enable selective refresh for widgets in customizer.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#customize-selective-refresh-widgets
     */
    add_theme_support('customize-selective-refresh-widgets');

    /**
     * Enable custom logo support.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#custom-logo
     */
    // Disabled: using custom Theme Options instead
    // add_theme_support('custom-logo', [
    //     'flex-width' => true,
    //     'flex-height' => true,
    //     'header-text' => '',
    //     'size' => 'full',
    // ]);

    /**
     * Enable starter content support.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#starter-content
     */
    add_theme_support('starter-content', [
        'posts' => [
            'home' => [
                'post_type'  => 'page',
                'post_title' => __('Home', 'sobe'),
                'post_content' => require resource_path('patterns/homepage-showcase.php'),
            ],
        ],
        'options' => [
            'show_on_front' => 'page',
            'page_on_front' => '{{home}}',
        ],
    ]);
}, 20);

/**
 * Register Theme Options in the Customizer.
 *
 * @return void
 */
add_action('customize_register', function (\WP_Customize_Manager $wp_customize) {
    $pfx = config('theme.prefix');

    $wp_customize->add_section("{$pfx}_header_options", [
        'title' => __('Header Options', 'sobe'),
        'priority' => 30,
    ]);

    // ── Header ─────────────────────────────────────────────────────────────
    $wp_customize->add_setting("{$pfx}_header_layout", [
        'default' => 'header-1',
        'sanitize_callback' => function ($value) {
            $allowed = ['header-1', 'header-2', 'header-3'];

            return in_array($value, $allowed, true) ? $value : 'header-1';
        },
        'transport' => 'refresh',
    ]);

    $wp_customize->add_control("{$pfx}_header_layout", [
        'label' => __('Header: Layout', 'sobe'),
        'section' => "{$pfx}_header_options",
        'type' => 'select',
        'choices' => [
            'header-1' => __('Header 1 (Default)', 'sobe'),
            'header-2' => __('Header 2', 'sobe'),
            'header-3' => __('Header 3', 'sobe'),
        ],
    ]);

    $wp_customize->add_setting("{$pfx}_enable_dark_toggle", [
        'default' => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
        'transport' => 'refresh',
    ]);

    $wp_customize->add_control("{$pfx}_enable_dark_toggle", [
        'label' => __('Header: Dark Mode Toggle', 'sobe'),
        'description' => __('Shows a sun/moon button in the site header.', 'sobe'),
        'section' => "{$pfx}_header_options",
        'type' => 'checkbox',
    ]);

    $wp_customize->add_setting("{$pfx}_enable_side_cart", [
        'default' => true,
        'sanitize_callback' => 'rest_sanitize_boolean',
        'transport' => 'refresh',
    ]);

    $wp_customize->add_control("{$pfx}_enable_side_cart", [
        'label' => __('Header: Side Cart', 'sobe'),
        'description' => __('Shows a cart button in the site header.', 'sobe'),
        'section' => "{$pfx}_header_options",
        'type' => 'checkbox',
    ]);

    $wp_customize->add_setting("{$pfx}_header_wishlist", [
        'default'           => false,
        'sanitize_callback' => 'rest_sanitize_boolean',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control("{$pfx}_header_wishlist", [
        'label'       => __('Header: Wishlist Icon', 'sobe'),
        'description' => __('Shows a heart icon in the site header (requires YITH WooCommerce Wishlist plugin).', 'sobe'),
        'section'     => "{$pfx}_header_options",
        'type'        => 'checkbox',
    ]);

    // ── Logo ────────────────────────────────────────────────────────────────
    $wp_customize->add_setting("{$pfx}_logo", [
        'default' => '',
        'sanitize_callback' => 'absint',
        'transport' => 'refresh',
    ]);

    $wp_customize->add_control(new \WP_Customize_Media_Control($wp_customize, "{$pfx}_logo", [
        'label' => __('Logo: Light', 'sobe'),
        'description' => __('Logo for light/white backgrounds. Recommended: transparent PNG or SVG.', 'sobe'),
        'section' => "{$pfx}_header_options",
        'mime_type' => 'image',
    ]));

    $wp_customize->add_setting("{$pfx}_dark_logo", [
        'default' => '',
        'sanitize_callback' => 'absint',
        'transport' => 'refresh',
    ]);

    $wp_customize->add_control(new \WP_Customize_Media_Control($wp_customize, "{$pfx}_dark_logo", [
        'label' => __('Logo: Dark', 'sobe'),
        'description' => __('Logo for dark backgrounds (dark mode). Recommended: transparent PNG or SVG.', 'sobe'),
        'section' => "{$pfx}_header_options",
        'mime_type' => 'image',
    ]));

    // ── Footer ─────────────────────────────────────────────────────────────
    $wp_customize->add_section("{$pfx}_footer_options", [
        'title'    => __('Footer Options', 'sobe'),
        'priority' => 31,
    ]);

    $wp_customize->add_setting("{$pfx}_footer_layout", [
        'default'           => 'layout-2',
        'sanitize_callback' => function ($value) {
            $allowed = ['layout-2', 'none'];
            return in_array($value, $allowed, true) ? $value : 'layout-2';
        },
        'transport' => 'refresh',
    ]);

    $wp_customize->add_control("{$pfx}_footer_layout", [
        'label'   => __('Footer: Layout', 'sobe'),
        'section' => "{$pfx}_footer_options",
        'type'    => 'select',
        'choices' => [
            'layout-2' => __('Minimal (Brand + Widgets)', 'sobe'),
            'none'     => __('None (Hidden)', 'sobe'),
        ],
    ]);

    $wp_customize->add_setting("{$pfx}_product_card_hover", [
        'default' => 'zoom',
        'sanitize_callback' => function ($value) {
            return in_array($value, ['zoom', 'swap'], true) ? $value : 'zoom';
        },
        'transport' => 'refresh',
    ]);

    $wp_customize->add_control("{$pfx}_product_card_hover", [
        'label' => __('Product Card: Image Hover Effect', 'sobe'),
        'section' => 'woocommerce_product_catalog',
        'type' => 'select',
        'choices' => [
            'zoom' => __('Zoom In', 'sobe'),
            'swap' => __('Swap to Gallery Image', 'sobe'),
        ],
    ]);

    $wp_customize->add_setting("{$pfx}_product_catalog_mobile_columns", [
        'default' => '2',
        'sanitize_callback' => function ($value) {
            return in_array($value, ['1', '2'], true) ? $value : '2';
        },
        'transport' => 'refresh',
    ]);

    $wp_customize->add_control("{$pfx}_product_catalog_mobile_columns", [
        'label' => __('Product Catalog: Mobile Columns', 'sobe'),
        'description' => __('Choose how many products appear per row on mobile screens.', 'sobe'),
        'section' => 'woocommerce_product_catalog',
        'type' => 'select',
        'choices' => [
            '1' => __('1 item per row', 'sobe'),
            '2' => __('2 items per row (Default)', 'sobe'),
        ],
    ]);

    $wp_customize->add_setting("{$pfx}_product_catalog_tablet_columns", [
        'default' => '3',
        'sanitize_callback' => function ($value) {
            return in_array($value, ['1', '2', '3'], true) ? $value : '3';
        },
        'transport' => 'refresh',
    ]);

    $wp_customize->add_control("{$pfx}_product_catalog_tablet_columns", [
        'label' => __('Product Catalog: Tablet Columns', 'sobe'),
        'description' => __('Choose how many products appear per row on tablet screens.', 'sobe'),
        'section' => 'woocommerce_product_catalog',
        'type' => 'select',
        'choices' => [
            '1' => __('1 item per row', 'sobe'),
            '2' => __('2 items per row', 'sobe'),
            '3' => __('3 items per row (Default)', 'sobe'),
        ],
    ]);

    // ── WooCommerce Shop Sidebar ────────────────────────────────────────
    $wp_customize->add_setting("{$pfx}_shop_sidebar_enabled", [
        'default' => true,
        'sanitize_callback' => 'rest_sanitize_boolean',
        'transport' => 'refresh',
    ]);

    $wp_customize->add_control("{$pfx}_shop_sidebar_enabled", [
        'label' => __('Shop Page: Sidebar', 'sobe'),
        'description' => __('Show filtering sidebar on shop and product category pages.', 'sobe'),
        'section' => 'woocommerce_product_catalog',
        'type' => 'checkbox',
    ]);
});

/**
 * Shortcode: [sobe_dark_toggle]
 * Renders the dark mode toggle anywhere — menus, widgets, content blocks.
 * Returns empty string when the Customizer master switch is off (upsell gate).
 */
add_shortcode(config('theme.prefix') . '_dark_toggle', function () {
    if (! get_theme_mod(config('theme.prefix') . '_enable_dark_toggle', false)) {
        return '';
    }

    return view('components.dark-mode-toggle')->render();
});

/**
 * Register the theme sidebars.
 *
 * @return void
 */
add_action('widgets_init', function () {
    $config = [
        'before_widget' => '<section class="widget %1$s %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h3>',
        'after_title' => '</h3>',
    ];

    register_sidebar([
        'name' => __('Primary', 'sobe'),
        'id' => 'sidebar-primary',
    ] + $config);

    register_sidebar([
        'name' => __('Footer', 'sobe'),
        'id' => 'sidebar-footer',
    ] + $config);

    register_sidebar([
        'name' => __('Shop Sidebar', 'sobe'),
        'id' => 'sidebar-shop',
    ] + $config);
});

/**
 * Fallback for YITH Wishlist Shortcode
 * Prevents raw shortcode text from leaking on the frontend if the plugin is deactivated.
 */
add_action('init', function () {
    // If the YITH Wishlist plugin is NOT active, take over its shortcode
    if (! defined('YITH_WCWL')) {
        add_shortcode('yith_wcwl_wishlist', function () {
            return '<div class="woocommerce-info">The wishlist feature is currently unavailable. Please activate the YITH Wishlist plugin.</div>';
        });
    }
});

/**
 * Store Notice Interception (Hybrid A+B Architecture)
 * Captures WC notices, suppresses redundant cart notices when Side Cart is active,
 * and bridges data to Alpine.js toast manager.
 */
add_action('init', function () {
    // Include the notice helpers
    $helper_path = get_theme_file_path('app/Helpers/notice-helpers.php');
    if (file_exists($helper_path)) {
        require_once $helper_path;
    }
});

// Intercept AJAX cart fragments to suppress notices when Side Cart is active
add_filter('woocommerce_add_to_cart_fragments', function ($fragments) {
    $sideCartEnabled = \App\Helpers\sobe_is_side_cart_enabled();

    if ($sideCartEnabled) {
        $emptyWrapper = \App\Helpers\sobe_get_empty_notices_wrapper();
        $fragments['div.woocommerce-notices-wrapper'] = $emptyWrapper;
        $fragments['.woocommerce-notices-wrapper'] = $emptyWrapper;
    } else {
        $notices = \App\Helpers\sobe_get_notices_for_toast();
        if (!empty($notices)) {
            $fragments['sobe_toast_data'] = \wp_json_encode($notices);
        }
    }

    return $fragments;
});

add_filter('wc_add_to_cart_message_html', function ($message) {
    if (\App\Helpers\sobe_is_side_cart_enabled()) {
        return '';
    }

    return $message;
}, 10, 1);

add_filter('woocommerce_add_to_cart_redirect', function ($url) {
    if (! \App\Helpers\sobe_is_side_cart_enabled() || \wp_doing_ajax() || ! \is_product()) {
        return $url;
    }

    $redirectUrl = $url ?: \wp_get_referer() ?: \home_url(\add_query_arg([]));
    $redirectUrl = \remove_query_arg('added-to-cart', $redirectUrl);

    return \add_query_arg('sobe_open_cart', '1', $redirectUrl);
}, 99);

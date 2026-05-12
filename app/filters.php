<?php

/**
 * Theme filters.
 */

namespace App;

/**
 * Allow SVG uploads.
 *
 * @param  array  $mimes
 * @return array
 */

/* // Allow SVG uploads (Requires server-side sanitization before production use)
add_filter('upload_mimes', function (array $mimes) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
});
*/

add_filter('excerpt_length', fn () => config('theme.excerpt_length'), 999);

/**
 * Cap related products to 4 and force a single 4-column row.
 */
add_filter('woocommerce_output_related_products_args', function (array $args): array {
    $args['posts_per_page'] = 4;
    $args['columns'] = 4;

    return $args;
});

/**
 * Cap upsells to 4 products displayed in a single row.
 */
add_filter('woocommerce_upsells_total', fn () => 4);
add_filter('woocommerce_upsell_display_default_columns', fn () => 4);

/**
 * Add "… Continued" to the excerpt.
 *
 * @return string
 */
add_filter('excerpt_more', function () {
    return sprintf(' &hellip; <a href="%s">%s</a>', get_permalink(), __('Continued', 'sobe'));
});

/**
 * Run shortcodes inside nav menu item titles.
 *
 * Allows text shortcodes (labels, badges, etc.) in menu item title fields.
 * Note: only use for non-interactive output. Interactive elements (buttons)
 * should use the CSS-class approach below to avoid invalid HTML nesting.
 *
 * @param  string  $title
 * @param  \WP_Post  $item
 * @param  \stdClass  $args
 * @param  int  $depth
 * @return string
 */
add_filter('nav_menu_item_title', function ($title, $item, $args, $depth) {
    return do_shortcode($title);
}, 10, 4);

/**
 * CSS-class trigger: replace menu items that have the class `sobe-dark-toggle`
 * with the dark mode toggle component.
 *
 * Usage: In Appearance → Menus, add a Custom Link, set the CSS class to
 * `sobe-dark-toggle`. The link label and URL are irrelevant — they get replaced.
 *
 * This replaces only the link content ($item_output), not the <li> wrapper,
 * so the result is a valid <li><button>…</button></li> with no nested anchors.
 * Returns empty string when the Customizer master switch is off (upsell gate).
 *
 * @param  string  $item_output
 * @param  \WP_Post  $item
 * @param  int  $depth
 * @param  \stdClass  $args
 * @return string
 */
add_filter('walker_nav_menu_start_el', function ($item_output, $item, $depth, $args) {
    $pfx = config('theme.prefix');
    $has_class = in_array("{$pfx}-dark-toggle", (array) $item->classes);
    $has_shortcode = has_shortcode($item->title, "{$pfx}_dark_toggle");

    if (! $has_class && ! $has_shortcode) {
        return $item_output;
    }

    if (! get_theme_mod("{$pfx}_enable_dark_toggle", false)) {
        return '';
    }

    return view('components.dark-mode-toggle')->render();
}, 10, 4);

<?php

/**
 * Generic theme helpers.
 */

namespace App;

function sobe_navigation_label(string $location, string $fallback): string
{
    $label = wp_get_nav_menu_name($location);

    return $label !== '' ? $label : $fallback;
}

/**
 * Get the front-end URL for the current request, even in AJAX context.
 *
 * In AJAX, the request URI is admin-ajax.php, so use the referer when present.
 */
function sobe_current_request_url(): string
{
    if (wp_doing_ajax()) {
        return wp_get_referer() ?: home_url('/');
    }

    return home_url(add_query_arg(null, null));
}

function sobe_navigation_menu(array $args = []): string
{
    $location = $args['theme_location'] ?? 'primary_navigation';
    $menuClass = $args['menu_class'] ?? 'menu';
    $depth = isset($args['depth']) ? (int) $args['depth'] : 2;

    if (has_nav_menu($location)) {
        return wp_nav_menu(array_merge([
            'theme_location' => $location,
            'container' => false,
            'echo' => false,
            'depth' => $depth,
        ], $args));
    }

    $items = wp_list_pages([
        'title_li' => '',
        'echo' => false,
        'depth' => $depth,
        'sort_column' => 'menu_order,post_title',
    ]);

    if (! is_string($items) || trim($items) === '') {
        $items = sprintf(
            '<li class="menu-item menu-item-home"><a href="%s">%s</a></li>',
            esc_url(home_url('/')),
            esc_html__('Home', 'sobe')
        );
    }

    $html = sprintf(
        '<ul class="%s">%s</ul>',
        esc_attr($menuClass),
        $items
    );

    return (string) apply_filters('sobe/navigation/fallback_html', $html, $args);
}

function sobe_footer_fallback_links(): array
{
    $links = [
        [
            'label' => __('Home', 'sobe'),
            'url' => home_url('/'),
        ],
    ];

    foreach ([
        'about' => __('About', 'sobe'),
        'contact' => __('Contact', 'sobe'),
    ] as $slug => $label) {
        $page = get_page_by_path($slug);
        if ($page instanceof \WP_Post) {
            $links[] = [
                'label' => $label,
                'url' => get_permalink($page),
            ];
        }
    }

    if (function_exists('wc_get_page_id')) {
        $shopPageId = (int) wc_get_page_id('shop');
        if ($shopPageId > 0) {
            $links[] = [
                'label' => __('Shop', 'sobe'),
                'url' => get_permalink($shopPageId),
            ];
        }
    }

    return (array) apply_filters('sobe/footer/fallback_links', $links);
}

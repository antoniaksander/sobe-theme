<?php

/**
 * Header search endpoint and runtime parameters.
 */

namespace App;



// ── Header search REST endpoint ───────────────────────────────────────────

add_action('rest_api_init', function (): void {
    $pfx = config('theme.prefix');

    register_rest_route("{$pfx}/v1", '/search', [
        'methods' => \WP_REST_Server::READABLE,
        'callback' => function (\WP_REST_Request $request): \WP_REST_Response {
            $pfx = config('theme.prefix');
            $q = sanitize_text_field($request->get_param('q') ?? $request->get_param('query') ?? '');
            $limit = min(10, max(1, (int) ($request->get_param('limit') ?? 5)));

            if (strlen($q) < 2) {
                return rest_ensure_response([]);
            }

            $cache_key = md5("sobe_search_{$q}_{$limit}");
            $cached = wp_cache_get($cache_key, 'sobe_search');
            if ($cached !== false) {
                return rest_ensure_response($cached);
            }

            $post_types = apply_filters('sobe/search/post_types', ['product', 'post', 'page'], $request);
            $post_types = is_array($post_types) ? $post_types : ['post', 'page'];
            $post_types = array_filter($post_types, 'post_type_exists');

            $queryArgs = apply_filters('sobe/search/query_args', [
                'post_type' => array_values($post_types),
                'post_status' => 'publish',
                'posts_per_page' => $limit,
                's' => $q,
            ], $q, $limit, $request);
            $queryArgs = is_array($queryArgs) ? $queryArgs : [];

            $query = new \WP_Query($queryArgs);

            $results = [];
            foreach ($query->posts as $post) {
                $price_html = '';
                $thumbnail = get_the_post_thumbnail_url($post->ID, 'thumbnail') ?: '';

                if ($post->post_type === 'product' && function_exists('wc_get_product')) {
                    $product = wc_get_product($post->ID);
                    $price_html = $product ? $product->get_price_html() : '';
                    $thumbnail = get_the_post_thumbnail_url($post->ID, 'woocommerce_thumbnail') ?: $thumbnail;
                }

                $result = [
                    'id' => $post->ID,
                    'title' => get_the_title($post->ID),
                    'url' => get_permalink($post->ID),
                    'price_html' => $price_html,
                    'thumbnail' => $thumbnail,
                ];

                $result = apply_filters('sobe/search/result', $result, $post);
                if (is_array($result)) {
                    $results[] = $result;
                }
            }

            $results = apply_filters('sobe/search/results', $results, $request);
            $results = is_array($results) ? $results : [];

            wp_cache_set($cache_key, $results, 'sobe_search', 60);

            return rest_ensure_response($results);
        },
        'permission_callback' => '__return_true',
        'args' => [
            'q' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
            'query' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
            'limit' => ['type' => 'integer', 'default' => 5, 'sanitize_callback' => 'absint'],
        ],
    ]);
});

// Index product_brand terms in Relevanssi Free (v4.26.1+).
// After deploy: WP Admin → Relevanssi → Indexing → enable taxonomy indexing → check product_brand → Re-index.
add_filter('relevanssi_taxonomies_to_index', function (array $taxonomies): array {
    if (! in_array('product_brand', $taxonomies, true)) {
        $taxonomies[] = 'product_brand';
    }

    return $taxonomies;
});

add_action('wp_head', function (): void {
    $pfx = config('theme.prefix');
    $params = apply_filters('sobe/search/params', [
        'restUrl' => rest_url(),
        'namespace' => "{$pfx}/v1",
        'searchPageUrl' => home_url('/'),
        'relevanssiActive' => function_exists('relevanssi_do_query'),
    ]);
    $params = is_array($params) ? $params : [];
    echo '<script>window.sobeSearchParams = '.\wp_json_encode($params).';</script>';
}, 5);

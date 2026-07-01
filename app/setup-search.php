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

            $results = sobe_search_brand_results($q);

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

// Guarantee Relevanssi always indexes products and the brand taxonomy, even if
// the Indexing tab checkboxes get reset (e.g. after a plugin update or on a
// fresh environment). get_option() fires an `option_{name}` filter for every
// call, including Relevanssi's own internal get_option() calls, so this is
// enforced regardless of what's saved in wp_options.
//
// Replaces a prior `relevanssi_taxonomies_to_index` filter, which Relevanssi
// never actually calls with apply_filters() and was therefore a no-op.
add_filter('option_relevanssi_index_post_types', function ($types) {
    $types = is_array($types) ? $types : [];

    if (post_type_exists('product') && ! in_array('product', $types, true)) {
        $types[] = 'product';
    }

    return $types;
});

add_filter('option_relevanssi_index_taxonomies_list', function ($taxonomies) {
    $taxonomies = is_array($taxonomies) ? $taxonomies : [];

    $brandTaxonomy = apply_filters('sobe/catalog_filters/brand_taxonomy', 'product_brand');

    if (is_string($brandTaxonomy) && taxonomy_exists($brandTaxonomy) && ! in_array($brandTaxonomy, $taxonomies, true)) {
        $taxonomies[] = $brandTaxonomy;
    }

    return $taxonomies;
});

/**
 * Brand terms matching the query, shaped like the post-based search results
 * so the drawer can render them with the same template.
 */
function sobe_search_brand_results(string $q): array
{
    $taxonomy = apply_filters('sobe/catalog_filters/brand_taxonomy', 'product_brand');

    if (! is_string($taxonomy) || ! taxonomy_exists($taxonomy)) {
        return [];
    }

    $terms = get_terms([
        'taxonomy' => $taxonomy,
        'name__like' => $q,
        'hide_empty' => true,
        'number' => 3,
    ]);

    if (is_wp_error($terms) || ! is_array($terms)) {
        return [];
    }

    $results = [];

    foreach ($terms as $term) {
        if (! $term instanceof \WP_Term) {
            continue;
        }

        $link = get_term_link($term);

        if (is_wp_error($link)) {
            continue;
        }

        $imageId = (int) get_term_meta($term->term_id, 'thumbnail_id', true);
        $thumbnail = $imageId > 0 ? (wp_get_attachment_image_url($imageId, 'thumbnail') ?: '') : '';

        // $term->count is unreliable here (WooCommerce filters it differently
        // depending on the WP_Term_Query shape), so count published products directly.
        $productCount = (new \WP_Query([
            'post_type' => 'product',
            'post_status' => 'publish',
            'tax_query' => [['taxonomy' => $taxonomy, 'field' => 'term_id', 'terms' => $term->term_id]],
            'fields' => 'ids',
            'posts_per_page' => 1,
            'no_found_rows' => false,
        ]))->found_posts;

        $results[] = [
            'id' => 'brand-'.$term->term_id,
            'title' => $term->name,
            'url' => (string) $link,
            'price_html' => '',
            'thumbnail' => $thumbnail,
            'post_type' => 'brand',
            'type_label' => __('Brand', 'sobe'),
            'excerpt' => sprintf(_n('%d product', '%d products', $productCount, 'sobe'), $productCount),
        ];
    }

    return $results;
}

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

<?php

/**

 * Demo catalog filter and swatch policy.

 */



namespace App;



use App\WooCommerce\FilterHandler;



if (! class_exists('WooCommerce')) {

    return;

}



// ── Same-taxonomy archive intersection ───────────────────────────────────────
//
// On a product taxonomy archive, a filter for that SAME taxonomy (e.g.
// /product-category/shoes/?filter_product_cat=boots) must intersect with the
// archive term, not replace it. Append both the selected term(s) and the
// archive term to the main product query so the result is shoes AND boots.

if (! function_exists('App\sobe_catalog_filter_slug_list')) {
    function sobe_catalog_filter_slug_list(mixed $value): array
    {
        $items = is_array($value) ? $value : preg_split('/[+\s]+/', (string) $value);

        if (! is_array($items)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn ($item): string => sanitize_title((string) $item),
            $items
        ))));
    }
}

if (! function_exists('App\sobe_catalog_filter_keys_for_taxonomy')) {
    function sobe_catalog_filter_keys_for_taxonomy(string $taxonomy): array
    {
        $taxonomy = sanitize_key($taxonomy);
        $keys = [$taxonomy, "filter_{$taxonomy}"];

        if ($taxonomy === 'product_cat') {
            $keys[] = 'product_cat';
            $keys[] = 'filter_product_cat';
        } elseif ($taxonomy === 'product_tag') {
            $keys[] = 'product_tag';
            $keys[] = 'filter_product_tag';
        } elseif (str_starts_with($taxonomy, 'pa_')) {
            $attribute = substr($taxonomy, 3);
            $keys[] = "filter_{$attribute}";
        }

        $brandTaxonomy = function_exists('App\sobe_product_brand_taxonomy')
            ? sobe_product_brand_taxonomy()
            : (string) apply_filters('sobe/catalog_filters/brand_taxonomy', 'product_brand');
        if ($taxonomy === sanitize_key($brandTaxonomy)) {
            array_push($keys, 'brand', 'filter_brand', 'product_brand', 'filter_product_brand');
        }

        return array_values(array_unique(array_filter($keys)));
    }
}

if (! function_exists('App\sobe_catalog_filter_request_slugs_for_taxonomy')) {
    function sobe_catalog_filter_request_slugs_for_taxonomy(string $taxonomy, array $source): array
    {
        $slugs = [];

        foreach (sobe_catalog_filter_keys_for_taxonomy($taxonomy) as $key) {
            if (! array_key_exists($key, $source)) {
                continue;
            }

            $slugs = array_merge($slugs, sobe_catalog_filter_slug_list($source[$key]));
        }

        return array_values(array_unique($slugs));
    }
}

if (! function_exists('App\sobe_append_catalog_tax_query_clause')) {
    function sobe_append_catalog_tax_query_clause(array $taxQuery, array $clause): array
    {
        $clauses = [];
        foreach ($taxQuery as $key => $item) {
            if ($key === 'relation' || ! is_array($item)) {
                continue;
            }

            $clauses[] = $item;
        }

        $clauses[] = $clause;

        return count($clauses) > 1
            ? array_merge(['relation' => 'AND'], $clauses)
            : $clauses;
    }
}

add_action('woocommerce_product_query', function (\WP_Query $query): void {
    if (! is_product_taxonomy()) {
        return;
    }

    $term = get_queried_object();
    if (! $term instanceof \WP_Term || empty($term->taxonomy) || empty($term->slug)) {
        return;
    }

    $taxonomy = sanitize_key((string) $term->taxonomy);
    if (! taxonomy_exists($taxonomy)) {
        return;
    }

    $archiveTerm = sanitize_title((string) $term->slug);
    $selectedTerms = array_values(array_diff(
        sobe_catalog_filter_request_slugs_for_taxonomy($taxonomy, $_GET),
        [$archiveTerm]
    ));

    if ($selectedTerms === []) {
        return;
    }

    $taxQuery = $query->get('tax_query');
    $taxQuery = is_array($taxQuery) ? $taxQuery : [];
    $taxQuery = sobe_append_catalog_tax_query_clause($taxQuery, [
        'taxonomy' => $taxonomy,
        'field' => 'slug',
        'terms' => $selectedTerms,
        'operator' => 'IN',
    ]);
    $query->set('tax_query', sobe_append_catalog_tax_query_clause($taxQuery, [
        'taxonomy' => $taxonomy,
        'field' => 'slug',
        'terms' => [$archiveTerm],
        'operator' => 'IN',
    ]));
}, 20);

// ── Catalog filter helpers ────────────────────────────────────────────────────

/**
 * Pluggable swatch colour fallback chain.
 *
 * 1. Native theme meta  (sobe_swatch_value)
 * 2. YITH WC Swatches   (yith_wccl_value)
 * 3. Generic colour hex (pa_color_hex)
 * 4. Developer escape hatch via filter
 */
function sobe_get_swatch_value(\WP_Term $term, string $attribute_name): ?string
{
    $id = $term->term_id;
    if ($v = get_term_meta($id, 'sobe_swatch_value', true)) {
        return (string) $v;
    }
    if ($v = get_term_meta($id, 'yith_wccl_value', true)) {
        return (string) $v;
    }
    if ($v = get_term_meta($id, 'pa_color_hex', true)) {
        return (string) $v;
    }

    $value = apply_filters('sobe_swatch_value', null, $term, $attribute_name);

    return apply_filters('sobe/catalog_filters/swatch_value', $value, $term, $attribute_name);
}

/**
 * Compute per-term product counts for each visible filter group, excluding
 * that group's own clause from the query so counts are interdependent.
 *
 * Uses wp_get_object_terms() instead of get_terms() — get_terms() has no
 * object_ids parameter. Guards against stores with >1000 products by falling
 * back to global counts.
 *
 * @param  array  $base_query_args  Full WP_Query args including all active filters.
 * @return array { categories: [{slug,name,count}], brands: [...], attributes: {attr_name: [...]} }
 */
function sobe_get_filtered_term_counts(array $base_query_args): array
{
    $result = ['categories' => [], 'brands' => [], 'attributes' => []];

    $get_counts = function (string $taxonomy) use ($base_query_args): array {
        $cache_key = 'sobe_filter_counts_'.$taxonomy.'_'.md5(serialize($base_query_args));
        $cached = wp_cache_get($cache_key, 'sobe_filters');
        if ($cached !== false) {
            return $cached;
        }

        $all_terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'orderby' => 'name']);
        if (is_wp_error($all_terms) || empty($all_terms)) {
            return [];
        }

        // Clone query args, removing this taxonomy's clause from tax_query
        $clone_args = $base_query_args;
        if (! empty($clone_args['tax_query'])) {
            $clauses = array_values(array_filter(
                (array) $clone_args['tax_query'],
                fn ($c) => is_array($c) && ($c['taxonomy'] ?? '') !== $taxonomy
            ));
            if (empty($clauses)) {
                unset($clone_args['tax_query']);
            } else {
                $clone_args['tax_query'] = count($clauses) > 1
                    ? array_merge(['relation' => 'AND'], $clauses)
                    : $clauses;
            }
        }

        $clone_args['fields'] = 'ids';
        $clone_args['posts_per_page'] = -1;
        $clone_args['no_found_rows'] = true;
        unset($clone_args['paged']);

        $q = new \WP_Query($clone_args);
        $ids = $q->posts;
        wp_reset_postdata();

        $term_data = [];

        if (empty($ids)) {
            foreach ($all_terms as $term) {
                $term_data[] = ['slug' => $term->slug, 'name' => $term->name, 'count' => 0];
            }
        } elseif (count($ids) > 1000) {
            // Fallback for large stores — global counts acceptable at this scale
            foreach ($all_terms as $term) {
                $term_data[] = ['slug' => $term->slug, 'name' => $term->name, 'count' => (int) $term->count];
            }
        } else {
            global $wpdb;
            $ids_list = implode(',', array_map('intval', $ids));

            $rows = $wpdb->get_results($wpdb->prepare("
                SELECT t.slug, t.name, COUNT(DISTINCT tr.object_id) as count
                FROM {$wpdb->term_relationships} tr
                JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
                WHERE tt.taxonomy = %s
                AND tr.object_id IN ($ids_list)
                GROUP BY t.term_id
            ", $taxonomy), ARRAY_A);

            $count_map = [];
            foreach ($rows as $row) {
                $count_map[$row['slug']] = (int) $row['count'];
            }

            foreach ($all_terms as $term) {
                $term_data[] = [
                    'slug' => $term->slug,
                    'name' => $term->name,
                    'count' => $count_map[$term->slug] ?? 0,
                ];
            }
        }

        wp_cache_set($cache_key, $term_data, 'sobe_filters', 60);

        return $term_data;
    };

    $result['categories'] = $get_counts('product_cat');

    $brandTaxonomy = apply_filters('sobe/catalog_filters/brand_taxonomy', 'product_brand');
    if (is_string($brandTaxonomy) && taxonomy_exists($brandTaxonomy)) {
        $result['brands'] = $get_counts($brandTaxonomy);
    }

    if (function_exists('wc_get_attribute_taxonomies')) {
        foreach (wc_get_attribute_taxonomies() as $attr) {
            $taxonomy = wc_attribute_taxonomy_name($attr->attribute_name);
            if (taxonomy_exists($taxonomy)) {
                $result['attributes'][$attr->attribute_name] = $get_counts($taxonomy);
            }
        }
    }

    return apply_filters('sobe/catalog_filters/term_counts', $result, $base_query_args);
}

// ── AJAX catalog filter handler ───────────────────────────────────────────────
// Core logic lives in App\WooCommerce\FilterHandler so it's testable without HTTP.

(new FilterHandler(config('theme.prefix')))->register();

function sobe_catalog_filter_params(): array
{
    $pfx = config('theme.prefix');
    $queried = get_queried_object();
    $contextType = is_search() ? 'search' : (is_product_taxonomy() ? 'taxonomy' : 'shop');
    $params = [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce("{$pfx}_nonce"),
        'action' => "{$pfx}_filter_products",
        'contextUrl' => sobe_current_request_url(),
        'contextType' => $contextType,
        'removeLabel' => __('Remove filter', 'sobe'),
        'removeSymbol' => '&times;',
        'errorText' => __('Something went wrong. Please refresh the page and try again.', 'sobe'),
    ];
    if (is_product_taxonomy() && isset($queried->taxonomy, $queried->slug)) {
        $params['archiveTaxonomy'] = $queried->taxonomy;
        $params['archiveTerm'] = $queried->slug;
        $params['queriedObjectId'] = (int) ($queried->term_id ?? 0);
    }

    return $params;
}

// Inline sobeCatalogParams on shop/taxonomy pages
add_action('wp_enqueue_scripts', function (): void {
    if (! is_shop() && ! is_product_taxonomy()) {
        return;
    }

    $params = sobe_catalog_filter_params();
    echo '<script>window.sobeCatalogParams = '.\wp_json_encode($params, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT).';</script>';
}, 20);

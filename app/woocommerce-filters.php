<?php

/**

 * Demo catalog filter and swatch policy.

 */



namespace App;



use App\WooCommerce\CatalogFilter\CatalogContext;
use App\WooCommerce\CatalogFilter\FilterStateParser;
use App\WooCommerce\CatalogFilter\QueryTransformer;
use App\WooCommerce\FilterHandler;



if (! class_exists('WooCommerce')) {

    return;

}



// ── Legacy attribute URL compatibility for the native main query ─────────────
//
// WooCommerce's own attribute-filter parsing (both its classic tax_query
// layered nav and the product-attributes-lookup-table filterer that replaces
// it when that feature is active) splits filter_{attribute} strictly on
// comma. FilterStateParser accepts legacy '+'/space-delimited values too,
// but the native main query never runs through FilterStateParser for
// attributes (see the woocommerce_product_query hook below) — so without
// this, a bookmarked/shared legacy attribute URL would parse correctly
// everywhere except here. Runs at 'init' priority 1, well before
// pre_get_posts / WC_Query::product_query(), so WooCommerce's own parsing
// (whichever internal mechanism is active) sees an already-canonical value.
add_action('init', function (): void {
    $_GET = FilterStateParser::normalizeLegacyAttributeEncoding($_GET);
}, 1);

// ── Normalized filter state on the native main query ─────────────────────────
//
// woocommerce_product_query fires at the end of WC_Query::product_query(),
// after WooCommerce has already applied its own baseline (catalog
// visibility, global hide-out-of-stock, native pa_* layered nav, and —
// because WC_Query::price_filter_post_clauses() reads $_GET directly —
// native min/max price). This hook only adds what WooCommerce doesn't
// already do for the main query: category/tag/brand selection, price_type
// (on_sale/full_price), and the generic stock-state filter. It deliberately
// never touches attribute tax_query or price — see
// QueryTransformer::applyToMainQuery() for why duplicating either would be
// wrong, not just redundant.
//
// This also replaces the previous same-taxonomy-only archive-intersection
// hook: that behavior is still enforced (see
// QueryTransformer::archiveIntersectionClause()), now as one part of the
// same normalized pipeline AJAX and load-more use, instead of a separate,
// narrower implementation.
add_action('woocommerce_product_query', function (\WP_Query $query): void {
    if (! $query->is_main_query()) {
        return;
    }

    $state = FilterStateParser::fromArray($_GET);
    $context = CatalogContext::fromCurrentQuery();

    QueryTransformer::applyToMainQuery($query, $state, $context);
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

/**
 * Compute the available product price range for the current non-price filters.
 *
 * The incoming query args include the active price constraint (the frontend
 * always submits min/max inputs), so strip it first — that way changing a
 * brand / category / attribute can re-scope the slider bounds to the
 * matching set, without the current price selection itself narrowing what
 * range the slider offers to move to. This strips both the legacy raw
 * `_price` meta_query shape (kept for any external `sobe/catalog_filters/
 * query_args` consumer that still adds one) and the
 * `sobe_catalog_price_filter` marker QueryTransformer::buildStandaloneQueryArgs()
 * sets — belt-and-suspenders, since the marker only takes effect while
 * $_GET['min_price']/['max_price'] are also set, which they won't be by the
 * time this runs (see FilterHandler::process() — this is called after
 * QueryTransformer::withPriceFilterQuery()'s scope has already restored
 * $_GET), but a caller building query_args directly rather than through
 * that method shouldn't have to know that.
 *
 * IMPORTANT: unlike sobe_get_filtered_term_counts(), which must run *inside*
 * QueryTransformer::withPriceFilterQuery()'s $_GET scope so its per-facet
 * counts reflect the active price constraint, this function must run
 * *outside* it — it deliberately wants the opposite: every other active
 * constraint applied, price itself excluded.
 *
 * @param  array  $base_query_args  Full WP_Query args including all active filters.
 * @return array{min: float, max: float}
 */
function sobe_get_filtered_price_range(array $base_query_args): array
{
    $query_args = $base_query_args;
    // Only the min/max price constraint is excluded (see docblock) —
    // price_type (on_sale/full_price)'s post__in/post__not_in, set by
    // QueryTransformer::applyPriceTypeToArgs(), is deliberately left in
    // place: that matches this function's pre-existing behavior (it only
    // ever stripped the _price meta_query key, never on_sale's clauses).
    unset($query_args['sobe_catalog_price_filter']);

    if (! empty($base_query_args['meta_query']) && is_array($base_query_args['meta_query'])) {
        $relation = isset($base_query_args['meta_query']['relation'])
            ? sanitize_key((string) $base_query_args['meta_query']['relation'])
            : '';
        $meta_query = [];
        foreach ($base_query_args['meta_query'] as $key => $clause) {
            if ($key === 'relation') {
                continue;
            }
            if (is_array($clause) && ($clause['key'] ?? '') === '_price') {
                continue;
            }
            $meta_query[] = $clause;
        }

        if ($meta_query === []) {
            unset($query_args['meta_query']);
        } elseif ($relation !== '' && count($meta_query) > 1) {
            $query_args['meta_query'] = array_merge(['relation' => $relation], $meta_query);
        } else {
            $query_args['meta_query'] = $meta_query;
        }
    }

    $query_args['fields'] = 'ids';
    $query_args['posts_per_page'] = -1;
    $query_args['no_found_rows'] = true;
    unset($query_args['paged']);

    $query = new \WP_Query($query_args);
    $ids = array_map('intval', (array) $query->posts);
    wp_reset_postdata();

    if ($ids === []) {
        return apply_filters('sobe/catalog_filters/price_range', ['min' => 0.0, 'max' => 0.0], $base_query_args);
    }

    global $wpdb;

    // wc_product_meta_lookup, not raw _price postmeta — this is the same
    // table WC_Query::price_filter_post_clauses() uses for the visible
    // query, so a variable product's real [min_price, max_price] range
    // (not just its parent _price, which is not authoritative for ranges)
    // is reflected here identically to what actually determined the
    // visible result set.
    // $ids are intval-cast above, so the IN list is safe to interpolate.
    $ids_list = implode(',', $ids);
    $row = $wpdb->get_row(
        "SELECT MIN(min_price) AS min_price, MAX(max_price) AS max_price
         FROM {$wpdb->wc_product_meta_lookup}
         WHERE product_id IN ($ids_list)"
    );

    $range = [
        'min' => (float) ($row->min_price ?? 0),
        'max' => (float) ($row->max_price ?? 0),
    ];

    return apply_filters('sobe/catalog_filters/price_range', $range, $base_query_args);
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

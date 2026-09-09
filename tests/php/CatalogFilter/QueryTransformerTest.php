<?php

/**
 * Unit tests for the WooCommerce-aware constraint builder shared by the
 * native main query (applyToMainQuery) and standalone AJAX/load-more
 * queries (buildStandaloneQueryArgs). These test the tax_query/meta_query
 * SHAPES produced and which native WooCommerce primitives get called
 * (wc_get_product_visibility_term_ids, wc_get_product_ids_on_sale) — not
 * actual database results, which need a real WordPress+WooCommerce
 * integration environment this repository does not yet have (see PR
 * description).
 */

use App\WooCommerce\CatalogFilter\CatalogContext;
use App\WooCommerce\CatalogFilter\FilterState;
use App\WooCommerce\CatalogFilter\QueryTransformer;
use Brain\Monkey\Functions;

beforeEach(function () {
    Functions\when('sanitize_key')->alias(fn ($v) => strtolower((string) $v));
    Functions\when('sanitize_title')->alias(fn ($v) => strtolower((string) $v));
    Functions\when('apply_filters')->alias(fn ($hook, $value = null) => $value);
    Functions\when('taxonomy_exists')->justReturn(true);
    Functions\when('add_filter')->justReturn(true);
    Functions\when('get_option')->justReturn('no');
    Functions\when('sanitize_text_field')->alias(fn ($v) => trim((string) $v));
});

function fakeState(array $overrides = []): FilterState
{
    return new FilterState(
        categorySlugs: $overrides['categorySlugs'] ?? [],
        tagSlugs: $overrides['tagSlugs'] ?? [],
        brandSlugs: $overrides['brandSlugs'] ?? [],
        attributes: $overrides['attributes'] ?? [],
        minPrice: $overrides['minPrice'] ?? null,
        maxPrice: $overrides['maxPrice'] ?? null,
        priceType: $overrides['priceType'] ?? FilterState::PRICE_TYPE_ALL,
        stockStatuses: $overrides['stockStatuses'] ?? [],
        orderby: $overrides['orderby'] ?? 'menu_order',
        order: $overrides['order'] ?? 'ASC',
        paged: $overrides['paged'] ?? 1,
        search: $overrides['search'] ?? null,
    );
}

// ── buildStandaloneQueryArgs(): the AJAX / load-more path ───────────────────

it('includes a product_visibility exclusion baseline WooCommerce applies natively on the main query', function () {
    Functions\when('wc_get_product_visibility_term_ids')->justReturn([
        'exclude-from-catalog' => 11,
        'exclude-from-search' => 12,
        'outofstock' => 13,
    ]);

    $args = QueryTransformer::buildStandaloneQueryArgs(fakeState(), CatalogContext::shop());

    $taxQuery = $args['tax_query'];
    $visibilityClause = collect_clauses($taxQuery, 'product_visibility');

    expect($visibilityClause)->not->toBeNull();
    expect($visibilityClause['operator'])->toBe('NOT IN');
    expect($visibilityClause['terms'])->toBe([11]);
});

it('also excludes the outofstock visibility term when the global hide-out-of-stock option is enabled', function () {
    Functions\when('get_option')->justReturn('yes');
    Functions\when('wc_get_product_visibility_term_ids')->justReturn([
        'exclude-from-catalog' => 11,
        'exclude-from-search' => 12,
        'outofstock' => 13,
    ]);

    $args = QueryTransformer::buildStandaloneQueryArgs(fakeState(), CatalogContext::shop());
    $visibilityClause = collect_clauses($args['tax_query'], 'product_visibility');

    expect($visibilityClause['terms'])->toBe([11, 13]);
});

it('uses exclude-from-search instead of exclude-from-catalog for a search context', function () {
    Functions\when('wc_get_product_visibility_term_ids')->justReturn([
        'exclude-from-catalog' => 11,
        'exclude-from-search' => 12,
        'outofstock' => 13,
    ]);

    $args = QueryTransformer::buildStandaloneQueryArgs(fakeState(['search' => 'boots']), CatalogContext::search('boots'));
    $visibilityClause = collect_clauses($args['tax_query'], 'product_visibility');

    expect($visibilityClause['terms'])->toBe([12]);
});

it('builds category/tag/brand IN clauses from the normalized state', function () {
    Functions\when('wc_get_product_visibility_term_ids')->justReturn([]);

    $args = QueryTransformer::buildStandaloneQueryArgs(
        fakeState(['categorySlugs' => ['shoes'], 'tagSlugs' => ['summer'], 'brandSlugs' => ['samelin']]),
        CatalogContext::shop()
    );

    expect(collect_clauses($args['tax_query'], 'product_cat'))->toBe(['taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => ['shoes'], 'operator' => 'IN']);
    expect(collect_clauses($args['tax_query'], 'product_tag'))->toBe(['taxonomy' => 'product_tag', 'field' => 'slug', 'terms' => ['summer'], 'operator' => 'IN']);
    expect(collect_clauses($args['tax_query'], 'product_brand'))->toBe(['taxonomy' => 'product_brand', 'field' => 'slug', 'terms' => ['samelin'], 'operator' => 'IN']);
});

it('builds an attribute clause with the AND/OR operator carried from FilterState', function () {
    Functions\when('wc_get_product_visibility_term_ids')->justReturn([]);

    $args = QueryTransformer::buildStandaloneQueryArgs(
        fakeState(['attributes' => ['pa_color' => ['terms' => ['red', 'blue'], 'operator' => 'AND']]]),
        CatalogContext::shop()
    );

    expect(collect_clauses($args['tax_query'], 'pa_color'))->toBe([
        'taxonomy' => 'pa_color', 'field' => 'slug', 'terms' => ['red', 'blue'], 'operator' => 'AND',
    ]);
});

it('intersects a same-taxonomy filter with the archive term rather than replacing it', function () {
    Functions\when('wc_get_product_visibility_term_ids')->justReturn([]);

    $context = CatalogContext::taxonomy('product_cat', 'shoes', 10);
    $args = QueryTransformer::buildStandaloneQueryArgs(fakeState(['categorySlugs' => ['boots']]), $context);

    $categoryClauses = array_values(array_filter($args['tax_query'], fn ($c) => is_array($c) && ($c['taxonomy'] ?? null) === 'product_cat'));

    expect($categoryClauses)->toHaveCount(2);
    expect(array_column($categoryClauses, 'terms'))->toContain(['boots'])->toContain(['shoes']);
});

it('preserves the archive term even when the user selects no filter for that taxonomy at all', function () {
    Functions\when('wc_get_product_visibility_term_ids')->justReturn([]);

    $context = CatalogContext::taxonomy('product_brand', 'samelin', 693);
    $args = QueryTransformer::buildStandaloneQueryArgs(fakeState(), $context);

    expect(collect_clauses($args['tax_query'], 'product_brand'))->toBe([
        'taxonomy' => 'product_brand', 'field' => 'slug', 'terms' => ['samelin'], 'operator' => 'IN',
    ]);
});

it('builds a _stock_status IN meta clause for a generic stock filter', function () {
    Functions\when('wc_get_product_visibility_term_ids')->justReturn([]);

    $args = QueryTransformer::buildStandaloneQueryArgs(fakeState(['stockStatuses' => ['instock']]), CatalogContext::shop());

    $metaQuery = array_values(array_filter($args['meta_query'], 'is_array'));
    expect($metaQuery)->toContain(['key' => '_stock_status', 'value' => ['instock'], 'compare' => 'IN']);
});

it('uses wc_get_product_ids_on_sale() for on_sale, never raw _sale_price meta', function () {
    Functions\when('wc_get_product_visibility_term_ids')->justReturn([]);
    Functions\when('wc_get_product_ids_on_sale')->justReturn([101, 102]);

    $args = QueryTransformer::buildStandaloneQueryArgs(fakeState(['priceType' => 'on_sale']), CatalogContext::shop());

    expect($args['post__in'])->toBe([101, 102]);
    expect($args)->not->toHaveKey('meta_query');
});

it('fails closed (matches nothing) when on_sale has zero currently-on-sale products', function () {
    Functions\when('wc_get_product_visibility_term_ids')->justReturn([]);
    Functions\when('wc_get_product_ids_on_sale')->justReturn([]);

    $args = QueryTransformer::buildStandaloneQueryArgs(fakeState(['priceType' => 'on_sale']), CatalogContext::shop());

    expect($args['post__in'])->toBe([0]);
});

it('excludes on-sale products via post__not_in for full_price', function () {
    Functions\when('wc_get_product_visibility_term_ids')->justReturn([]);
    Functions\when('wc_get_product_ids_on_sale')->justReturn([101, 102]);

    $args = QueryTransformer::buildStandaloneQueryArgs(fakeState(['priceType' => 'full_price']), CatalogContext::shop());

    expect($args['post__not_in'])->toBe([101, 102]);
});

it('marks the query for WooCommerce price-lookup-table filtering when a price range is set', function () {
    Functions\when('wc_get_product_visibility_term_ids')->justReturn([]);

    $args = QueryTransformer::buildStandaloneQueryArgs(fakeState(['minPrice' => 10.0, 'maxPrice' => 50.0]), CatalogContext::shop());

    expect($args['sobe_catalog_price_filter'] ?? null)->toBeTrue();
});

it('does not mark the query for price filtering when no price range is set', function () {
    Functions\when('wc_get_product_visibility_term_ids')->justReturn([]);

    $args = QueryTransformer::buildStandaloneQueryArgs(fakeState(), CatalogContext::shop());

    expect($args)->not->toHaveKey('sobe_catalog_price_filter');
});

/** Find a single tax_query clause for a taxonomy (asserts at most one). */
function collect_clauses(array $taxQuery, string $taxonomy): ?array
{
    $matches = array_values(array_filter($taxQuery, fn ($c) => is_array($c) && ($c['taxonomy'] ?? null) === $taxonomy));

    return $matches[0] ?? null;
}

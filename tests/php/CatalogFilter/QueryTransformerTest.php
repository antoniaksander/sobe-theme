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

it('translates FilterState OPERATOR_AND to the literal WP_Tax_Query operator AND', function () {
    Functions\when('wc_get_product_visibility_term_ids')->justReturn([]);

    $args = QueryTransformer::buildStandaloneQueryArgs(
        fakeState(['attributes' => ['pa_color' => ['terms' => ['red', 'blue'], 'operator' => 'AND']]]),
        CatalogContext::shop()
    );

    expect(collect_clauses($args['tax_query'], 'pa_color'))->toBe([
        'taxonomy' => 'pa_color', 'field' => 'slug', 'terms' => ['red', 'blue'], 'operator' => 'AND',
    ]);
});

it('translates FilterState OPERATOR_OR to WP_Tax_Query\'s IN, never the literal string "OR"', function () {
    // WP_Tax_Query has no 'OR' operator at all (only IN, NOT IN, AND,
    // EXISTS, NOT EXISTS) -- an unrecognized operator string is silently
    // ignored by WordPress core, which is exactly how this shipped broken
    // in a real WordPress+WooCommerce environment: the array shape looked
    // correct (this is why a shape-only assertion here would not have
    // caught it), WordPress accepted the clause, and then applied no
    // constraint from it at all. Confirmed via real WP_Query execution
    // during PR #140 review -- see the PR description's runtime matrix.
    Functions\when('wc_get_product_visibility_term_ids')->justReturn([]);

    $args = QueryTransformer::buildStandaloneQueryArgs(
        fakeState(['attributes' => ['pa_size' => ['terms' => ['42', '43'], 'operator' => 'OR']]]),
        CatalogContext::shop()
    );

    $clause = collect_clauses($args['tax_query'], 'pa_size');
    expect($clause['operator'])->toBe('IN');
    expect($clause['operator'])->not->toBe('OR');
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

it('intersects an existing post__in with on-sale IDs and normalizes integers and duplicates', function () {
    Functions\when('wc_get_product_visibility_term_ids')->justReturn([]);
    Functions\when('wc_get_product_ids_on_sale')->justReturn(['102', 103, 103, -104]);

    $args = QueryTransformer::buildStandaloneQueryArgs(
        fakeState(['priceType' => 'on_sale']),
        CatalogContext::shop(),
        ['post__in' => ['101', 102, 102, 104]]
    );

    expect($args['post__in'])->toBe([102, 104]);
    expect($args)->not->toHaveKey('post__not_in');
});

it('subtracts on-sale IDs from an existing post__in for full_price', function () {
    Functions\when('wc_get_product_visibility_term_ids')->justReturn([]);
    Functions\when('wc_get_product_ids_on_sale')->justReturn([102, 104]);

    $args = QueryTransformer::buildStandaloneQueryArgs(
        fakeState(['priceType' => 'full_price']),
        CatalogContext::shop(),
        ['post__in' => [101, 102, 103, 104]]
    );

    expect($args['post__in'])->toBe([101, 103]);
    expect($args)->not->toHaveKey('post__not_in');
});

it('fails closed when full_price removes every ID from an existing post__in', function () {
    Functions\when('wc_get_product_visibility_term_ids')->justReturn([]);
    Functions\when('wc_get_product_ids_on_sale')->justReturn([101, 102]);

    $args = QueryTransformer::buildStandaloneQueryArgs(
        fakeState(['priceType' => 'full_price']),
        CatalogContext::shop(),
        ['post__in' => [101, 102]]
    );

    expect($args['post__in'])->toBe([0]);
    expect($args)->not->toHaveKey('post__not_in');
});

it('constructs an on-sale inclusion set after applying an existing post__not_in', function () {
    Functions\when('wc_get_product_visibility_term_ids')->justReturn([]);
    Functions\when('wc_get_product_ids_on_sale')->justReturn([101, 102, 103]);

    $args = QueryTransformer::buildStandaloneQueryArgs(
        fakeState(['priceType' => 'on_sale']),
        CatalogContext::shop(),
        ['post__not_in' => ['102', 999]]
    );

    expect($args['post__in'])->toBe([101, 103]);
    expect($args)->not->toHaveKey('post__not_in');
});

it('fails closed when an existing post__not_in excludes every on-sale ID', function () {
    Functions\when('wc_get_product_visibility_term_ids')->justReturn([]);
    Functions\when('wc_get_product_ids_on_sale')->justReturn([101, 102]);

    $args = QueryTransformer::buildStandaloneQueryArgs(
        fakeState(['priceType' => 'on_sale']),
        CatalogContext::shop(),
        ['post__not_in' => [101, 102]]
    );

    expect($args['post__in'])->toBe([0]);
    expect($args)->not->toHaveKey('post__not_in');
});

it('merges and normalizes existing exclusions with on-sale IDs for full_price', function () {
    Functions\when('wc_get_product_visibility_term_ids')->justReturn([]);
    Functions\when('wc_get_product_ids_on_sale')->justReturn(['102', 103, 103]);

    $args = QueryTransformer::buildStandaloneQueryArgs(
        fakeState(['priceType' => 'full_price']),
        CatalogContext::shop(),
        ['post__not_in' => ['101', 102, 101]]
    );

    expect($args['post__not_in'])->toBe([101, 102, 103]);
    expect($args)->not->toHaveKey('post__in');
});

it('normalizes an incoming post__in plus post__not_in even without a price-type filter', function () {
    Functions\when('wc_get_product_visibility_term_ids')->justReturn([]);

    $args = QueryTransformer::buildStandaloneQueryArgs(
        fakeState(),
        CatalogContext::shop(),
        ['post__in' => ['101', 102, 102, 103], 'post__not_in' => ['102', 999]]
    );

    expect($args['post__in'])->toBe([101, 103]);
    expect($args)->not->toHaveKey('post__not_in');
});

it('fails closed when incoming post__not_in removes every incoming post__in ID', function () {
    Functions\when('wc_get_product_visibility_term_ids')->justReturn([]);

    $args = QueryTransformer::buildStandaloneQueryArgs(
        fakeState(),
        CatalogContext::shop(),
        ['post__in' => [101], 'post__not_in' => [101]]
    );

    expect($args['post__in'])->toBe([0]);
    expect($args)->not->toHaveKey('post__not_in');
});

it('preserves an existing post__in for full_price when there are no products on sale', function () {
    Functions\when('wc_get_product_visibility_term_ids')->justReturn([]);
    Functions\when('wc_get_product_ids_on_sale')->justReturn([]);

    $args = QueryTransformer::buildStandaloneQueryArgs(
        fakeState(['priceType' => 'full_price']),
        CatalogContext::shop(),
        ['post__in' => ['101', 102, 102]]
    );

    expect($args['post__in'])->toBe([101, 102]);
    expect($args)->not->toHaveKey('post__not_in');
});

it('normalizes the main-query post ID constraints before applying full_price', function () {
    Functions\when('wc_get_product_ids_on_sale')->justReturn([101, 102]);

    $queryVars = [
        'tax_query' => [],
        'post__in' => ['101', 102, 103],
        'post__not_in' => [103, 999],
    ];
    $query = Mockery::mock('WP_Query');
    $query->shouldReceive('get')->andReturnUsing(fn ($key) => $queryVars[$key] ?? '');
    $query->shouldReceive('set')->andReturnUsing(function ($key, $value) use (&$queryVars): void {
        $queryVars[$key] = $value;
    });

    QueryTransformer::applyToMainQuery(
        $query,
        fakeState(['priceType' => 'full_price']),
        CatalogContext::shop()
    );

    expect($queryVars['post__in'])->toBe([0]);
    expect($queryVars['post__not_in'])->toBe([]);
});

it('materializes an existing main-query exclusion into the on-sale inclusion set', function () {
    Functions\when('wc_get_product_ids_on_sale')->justReturn([101, 102, 103]);

    $queryVars = [
        'tax_query' => [],
        'post__in' => [],
        'post__not_in' => ['102', 999],
    ];
    $query = Mockery::mock('WP_Query');
    $query->shouldReceive('get')->andReturnUsing(fn ($key) => $queryVars[$key] ?? '');
    $query->shouldReceive('set')->andReturnUsing(function ($key, $value) use (&$queryVars): void {
        $queryVars[$key] = $value;
    });

    QueryTransformer::applyToMainQuery(
        $query,
        fakeState(['priceType' => 'on_sale']),
        CatalogContext::shop()
    );

    expect($queryVars['post__in'])->toBe([101, 103]);
    expect($queryVars['post__not_in'])->toBe([]);
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

// ── TYPE_INVALID must fail closed (zero results), never fall through ───────

it('forces zero results for an invalid context, rather than an unscoped query', function () {
    $args = QueryTransformer::buildStandaloneQueryArgs(fakeState(), CatalogContext::invalid());

    expect($args['post__in'])->toBe([0]);
});

it('ignores the rest of the filter state entirely once the context is invalid', function () {
    // Even a "wide open" filter state (nothing selected) must not leak
    // through as an effectively-unscoped query once the context itself is
    // invalid -- there is no meaningful selection that makes an invalid
    // archive claim safe to serve.
    $args = QueryTransformer::buildStandaloneQueryArgs(
        fakeState(['categorySlugs' => ['shoes']]),
        CatalogContext::invalid()
    );

    expect($args['post__in'])->toBe([0]);
    expect($args)->not->toHaveKey('tax_query');
});

// ── withOrderbyScope() ───────────────────────────────────────────────────────

it('scopes $_GET[orderby] for the duration of the callback and restores it after', function () {
    unset($_GET['orderby']);

    $seenDuring = QueryTransformer::withOrderbyScope('popularity', function () {
        return $_GET['orderby'] ?? null;
    });

    expect($seenDuring)->toBe('popularity');
    expect(array_key_exists('orderby', $_GET))->toBeFalse();
});

it('restores a pre-existing $_GET[orderby] value rather than just unsetting it', function () {
    $_GET['orderby'] = 'date';

    QueryTransformer::withOrderbyScope('popularity', function () {
        expect($_GET['orderby'])->toBe('popularity');
    });

    expect($_GET['orderby'])->toBe('date');
    unset($_GET['orderby']);
});

it('does not touch $_GET at all for an empty orderby', function () {
    unset($_GET['orderby']);

    QueryTransformer::withOrderbyScope('', function () {
        expect(array_key_exists('orderby', $_GET))->toBeFalse();
    });
});

it('restores $_GET[orderby] even if the callback throws', function () {
    $_GET['orderby'] = 'date';

    try {
        QueryTransformer::withOrderbyScope('popularity', function () {
            throw new \RuntimeException('boom');
        });
    } catch (\RuntimeException) {
        // expected
    }

    expect($_GET['orderby'])->toBe('date');
    unset($_GET['orderby']);
});

// ── buildStandaloneQueryArgs() must extend $baseArgs, never silently replace it ──
//
// Confirmed via a real WordPress+WooCommerce request during PR #140 review:
// the plain (no-active-filter) load-more handler parses its FilterState from
// a params array that has no 'paged' key at all (pagination arrives as a
// separate $_POST['page'], not part of filter state), so $state->paged is
// always its default of 1. Before this fix, buildStandaloneQueryArgs()
// unconditionally set $args['paged'] = $state->paged at the end, silently
// discarding the real page number the caller had explicitly passed via
// $baseArgs — every "load more" request re-served page 1's products.

it('does not overwrite a paged value the caller explicitly passed in $baseArgs', function () {
    Functions\when('wc_get_product_visibility_term_ids')->justReturn([]);

    $args = QueryTransformer::buildStandaloneQueryArgs(
        fakeState(), // default paged: 1 — the caller's baseArgs must still win
        CatalogContext::shop(),
        ['paged' => 3]
    );

    expect($args['paged'])->toBe(3);
});

it('falls back to FilterState paged only when the caller did not specify one', function () {
    Functions\when('wc_get_product_visibility_term_ids')->justReturn([]);

    $args = QueryTransformer::buildStandaloneQueryArgs(fakeState(['paged' => 5]), CatalogContext::shop());

    expect($args['paged'])->toBe(5);
});

it('does not overwrite an explicit orderby/order the caller passed in $baseArgs', function () {
    Functions\when('wc_get_product_visibility_term_ids')->justReturn([]);

    $args = QueryTransformer::buildStandaloneQueryArgs(
        fakeState(['orderby' => 'menu_order', 'order' => 'ASC']),
        CatalogContext::shop(),
        ['orderby' => 'price', 'order' => 'DESC']
    );

    expect($args['orderby'])->toBe('price');
    expect($args['order'])->toBe('DESC');
});

/** Find a single tax_query clause for a taxonomy (asserts at most one). */
function collect_clauses(array $taxQuery, string $taxonomy): ?array
{
    $matches = array_values(array_filter($taxQuery, fn ($c) => is_array($c) && ($c['taxonomy'] ?? null) === $taxonomy));

    return $matches[0] ?? null;
}

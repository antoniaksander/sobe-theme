<?php

/**
 * Unit tests for the catalog AJAX filter query builders.
 *
 * These cover the security-sensitive logic (taxonomy/meta query construction
 * and the result-count output) without bootstrapping WordPress — WP functions
 * are mocked with Brain Monkey.
 */

use App\WooCommerce\FilterHandler;
use Brain\Monkey\Functions;

beforeEach(function () {
    Functions\when('sanitize_text_field')->returnArg();
    Functions\when('sanitize_title')->returnArg();
    Functions\when('sanitize_key')->alias(fn ($value) => strtolower((string) $value));
    Functions\when('apply_filters')->alias(fn ($hook, $value = null) => $value);
    Functions\when('taxonomy_exists')->justReturn(true);
    Functions\when('__')->returnArg();
});

/** Assert a query clause (associative array) is present, order-independent. */
function expectClause(array $clauses, array $expected): void
{
    expect(in_array($expected, $clauses, false))->toBeTrue();
}

it('builds tax-query clauses for category, tag, attribute and brand filters', function () {
    $clauses = invokeMethod(new FilterHandler('sobe'), 'buildTaxQuery', [[
        'product_cat' => 'shoes',
        'product_tag' => 'summer',
        'filter_color' => ['red', 'blue'],
        'sobe_brands' => ['nike'],
    ]]);

    expect($clauses)->toHaveCount(4);
    expectClause($clauses, ['taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => ['shoes'], 'operator' => 'IN']);
    expectClause($clauses, ['taxonomy' => 'product_tag', 'field' => 'slug', 'terms' => ['summer'], 'operator' => 'IN']);
    expectClause($clauses, ['taxonomy' => 'pa_color', 'field' => 'slug', 'terms' => ['red', 'blue'], 'operator' => 'IN']);
    expectClause($clauses, ['taxonomy' => 'product_brand', 'field' => 'slug', 'terms' => ['nike'], 'operator' => 'IN']);
});

it('parses +-delimited filter values into multiple term slugs', function () {
    $clauses = invokeMethod(new FilterHandler('sobe'), 'buildTaxQuery', [[
        'filter_product_cat' => 'shoes+boots',
        'filter_color' => 'red+blue',
        'filter_product_brand' => 'nike+adidas',
    ]]);

    expectClause($clauses, ['taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => ['shoes', 'boots'], 'operator' => 'IN']);
    expectClause($clauses, ['taxonomy' => 'pa_color', 'field' => 'slug', 'terms' => ['red', 'blue'], 'operator' => 'IN']);
    expectClause($clauses, ['taxonomy' => 'product_brand', 'field' => 'slug', 'terms' => ['nike', 'adidas'], 'operator' => 'IN']);
});

it('returns no tax-query clauses for an empty filter state', function () {
    expect(invokeMethod(new FilterHandler('sobe'), 'buildTaxQuery', [[]]))->toBe([]);
});

it('builds a BETWEEN price clause plus on-sale meta', function () {
    $clauses = invokeMethod(new FilterHandler('sobe'), 'buildMetaQuery', [[
        'min_price' => '10',
        'max_price' => '50',
        'price_type' => 'on_sale',
    ]]);

    expectClause($clauses, ['key' => '_price', 'type' => 'NUMERIC', 'value' => [10.0, 50.0], 'compare' => 'BETWEEN']);
    expectClause($clauses, ['key' => '_sale_price', 'value' => '', 'compare' => '!=']);
    expectClause($clauses, ['key' => '_sale_price', 'value' => '0', 'compare' => '>', 'type' => 'NUMERIC']);
});

it('builds a >= price clause when only a minimum price is set', function () {
    $clauses = invokeMethod(new FilterHandler('sobe'), 'buildMetaQuery', [['min_price' => '20']]);

    expectClause($clauses, ['key' => '_price', 'type' => 'NUMERIC', 'value' => 20.0, 'compare' => '>=']);
});

it('builds a <= price clause when only a maximum price is set', function () {
    $clauses = invokeMethod(new FilterHandler('sobe'), 'buildMetaQuery', [['max_price' => '99']]);

    expectClause($clauses, ['key' => '_price', 'type' => 'NUMERIC', 'value' => 99.0, 'compare' => '<=']);
});

it('renders a paginated result count', function () {
    $html = invokeMethod(new FilterHandler('sobe'), 'generateCountHtml', [25, 2, 10]);

    expect($html)
        ->toContain('data-result-count')
        ->toContain('11')
        ->toContain('20')
        ->toContain('25');
});

it('renders the single-result count', function () {
    $html = invokeMethod(new FilterHandler('sobe'), 'generateCountHtml', [1, 1, 10]);

    expect($html)->toContain('woocommerce-result-count');
});

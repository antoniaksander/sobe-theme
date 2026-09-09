<?php

/**
 * Unit tests for the single normalized filter-state parser shared by direct
 * GET, filter AJAX, and load-more (see FilterHandler::process(),
 * woocommerce-filters.php's woocommerce_product_query hook, and
 * woocommerce-catalog.php's load-more handler — all three now call
 * FilterStateParser::fromArray() instead of parsing independently).
 */

use App\WooCommerce\CatalogFilter\FilterState;
use App\WooCommerce\CatalogFilter\FilterStateParser;
use Brain\Monkey\Functions;

beforeEach(function () {
    Functions\when('sanitize_title')->alias(fn ($v) => strtolower(trim((string) $v)));
    Functions\when('sanitize_key')->alias(fn ($v) => strtolower((string) $v));
    Functions\when('sanitize_text_field')->alias(fn ($v) => trim((string) $v));
    Functions\when('apply_filters')->alias(fn ($hook, $value = null) => $value);
    Functions\when('taxonomy_exists')->justReturn(true);

    $attrs = [
        (object) ['attribute_name' => 'color'],
        (object) ['attribute_name' => 'size'],
    ];
    Functions\when('wc_get_attribute_taxonomies')->justReturn($attrs);
    Functions\when('wc_attribute_taxonomy_name')->alias(fn ($name) => 'pa_'.$name);
});

// ── Category / tag ───────────────────────────────────────────────────────────

it('parses category and tag slugs from either bare or filter_-prefixed keys', function () {
    $state = FilterStateParser::fromArray(['product_cat' => 'shoes', 'filter_product_tag' => 'summer']);

    expect($state->categorySlugs)->toBe(['shoes']);
    expect($state->tagSlugs)->toBe(['summer']);
});

// ── Brand aliasing (the confirmed filter_product_brand=samelin defect) ──────

it('normalizes every documented brand alias to the same brandSlugs list', function () {
    foreach (['brand', 'filter_brand', 'product_brand', 'filter_product_brand', 'sobe_brands'] as $key) {
        $state = FilterStateParser::fromArray([$key => 'samelin']);
        expect($state->brandSlugs)->toBe(['samelin']);
    }
});

it('never maps a brand alias onto a pa_product_brand attribute taxonomy', function () {
    $state = FilterStateParser::fromArray(['filter_product_brand' => 'samelin']);

    expect($state->brandSlugs)->toBe(['samelin']);
    expect($state->attributes)->not->toHaveKey('pa_product_brand');
});

it('deduplicates brand slugs supplied via multiple aliases at once', function () {
    $state = FilterStateParser::fromArray(['brand' => 'samelin', 'product_brand' => 'samelin']);

    expect($state->brandSlugs)->toBe(['samelin']);
});

// ── Legacy '+' vs canonical ',' encoding ────────────────────────────────────

it('parses legacy +-delimited multi-value filters', function () {
    $state = FilterStateParser::fromArray(['filter_product_cat' => 'shoes+boots']);

    expect($state->categorySlugs)->toBe(['shoes', 'boots']);
});

it('parses canonical comma-delimited multi-value filters identically', function () {
    $state = FilterStateParser::fromArray(['filter_product_cat' => 'shoes,boots']);

    expect($state->categorySlugs)->toBe(['shoes', 'boots']);
});

it('round-trips a legacy URL and a canonical URL to the same normalized state', function () {
    $legacy = FilterStateParser::fromArray(['filter_color' => 'red+blue']);
    $canonical = FilterStateParser::fromArray(['filter_color' => 'red,blue']);

    expect($legacy->attributes)->toBe($canonical->attributes);
});

it('accepts an already-decoded array (the AJAX filter_state JSON shape)', function () {
    $state = FilterStateParser::fromArray(['filter_product_cat' => ['shoes', 'boots']]);

    expect($state->categorySlugs)->toBe(['shoes', 'boots']);
});

// ── Attributes: registered-only allowlist + AND/OR semantics ───────────────

it('only accepts registered product attribute taxonomies, not an arbitrary key', function () {
    $state = FilterStateParser::fromArray(['filter_color' => 'red', 'filter_not_a_real_attribute' => 'x']);

    expect($state->attributes)->toHaveKey('pa_color');
    expect($state->attributes)->not->toHaveKey('pa_not_a_real_attribute');
});

it('defaults attribute selection to OR (IN) semantics', function () {
    $state = FilterStateParser::fromArray(['filter_color' => 'red,blue']);

    expect($state->attributes['pa_color']['operator'])->toBe(FilterState::OPERATOR_OR);
    expect($state->attributes['pa_color']['terms'])->toBe(['red', 'blue']);
});

it('honours query_type_{attribute}=and using WooCommerce\'s own native param name', function () {
    $state = FilterStateParser::fromArray(['filter_color' => 'red,blue', 'query_type_color' => 'and']);

    expect($state->attributes['pa_color']['operator'])->toBe(FilterState::OPERATOR_AND);
});

it('handles multiple simultaneously selected attributes independently', function () {
    $state = FilterStateParser::fromArray([
        'filter_color' => 'red',
        'filter_size' => 'm,l',
        'query_type_size' => 'and',
    ]);

    expect($state->attributes['pa_color'])->toBe(['terms' => ['red'], 'operator' => FilterState::OPERATOR_OR]);
    expect($state->attributes['pa_size'])->toBe(['terms' => ['m', 'l'], 'operator' => FilterState::OPERATOR_AND]);
});

// ── Price ────────────────────────────────────────────────────────────────────

it('parses a valid min/max price range', function () {
    $state = FilterStateParser::fromArray(['min_price' => '10', 'max_price' => '50']);

    expect($state->minPrice)->toBe(10.0);
    expect($state->maxPrice)->toBe(50.0);
});

it('swaps an inverted min/max range rather than producing a query that matches nothing', function () {
    $state = FilterStateParser::fromArray(['min_price' => '50', 'max_price' => '10']);

    expect($state->minPrice)->toBe(10.0);
    expect($state->maxPrice)->toBe(50.0);
});

it('clamps a negative price to zero', function () {
    $state = FilterStateParser::fromArray(['min_price' => '-20']);

    expect($state->minPrice)->toBe(0.0);
});

it('rejects a non-numeric price as absent rather than crashing', function () {
    $state = FilterStateParser::fromArray(['min_price' => 'not-a-number']);

    expect($state->minPrice)->toBeNull();
});

it('leaves price null when absent', function () {
    $state = FilterStateParser::fromArray([]);

    expect($state->minPrice)->toBeNull();
    expect($state->maxPrice)->toBeNull();
});

// ── price_type ───────────────────────────────────────────────────────────────

it('accepts every documented price_type value', function () {
    foreach (['all', 'on_sale', 'full_price'] as $type) {
        expect(FilterStateParser::fromArray(['price_type' => $type])->priceType)->toBe($type);
    }
});

it('falls back to "all" for an unknown price_type rather than failing open to something unintended', function () {
    $state = FilterStateParser::fromArray(['price_type' => 'literally_anything_else']);

    expect($state->priceType)->toBe(FilterState::PRICE_TYPE_ALL);
});

// ── stock ────────────────────────────────────────────────────────────────────

it('accepts supported stock states and drops unsupported ones', function () {
    $state = FilterStateParser::fromArray(['stock_status' => ['instock', 'outofstock', 'not_a_real_status']]);

    expect($state->stockStatuses)->toBe(['instock', 'outofstock']);
});

// ── orderby / order ──────────────────────────────────────────────────────────

it('accepts a known WooCommerce catalog orderby key', function () {
    expect(FilterStateParser::fromArray(['orderby' => 'popularity'])->orderby)->toBe('popularity');
});

it('falls back to menu_order for an unrecognised orderby value', function () {
    expect(FilterStateParser::fromArray(['orderby' => 'drop table products'])->orderby)->toBe('menu_order');
});

it('normalizes order to ASC or DESC only', function () {
    expect(FilterStateParser::fromArray(['order' => 'desc'])->order)->toBe('DESC');
    expect(FilterStateParser::fromArray(['order' => 'nonsense'])->order)->toBe('ASC');
});

// ── pagination ───────────────────────────────────────────────────────────────

it('defaults paged to 1', function () {
    expect(FilterStateParser::fromArray([])->paged)->toBe(1);
});

it('rejects a zero/negative paged value back to 1', function () {
    expect(FilterStateParser::fromArray(['paged' => 0])->paged)->toBe(1);
    expect(FilterStateParser::fromArray(['paged' => -5])->paged)->toBe(1);
});

it('accepts a valid positive paged value', function () {
    expect(FilterStateParser::fromArray(['paged' => 3])->paged)->toBe(3);
});

// ── malformed input fails closed, not throws ────────────────────────────────

it('never throws on a fully malformed/empty payload', function () {
    $state = FilterStateParser::fromArray([]);

    expect($state)->toBeInstanceOf(FilterState::class);
    expect($state->hasAnyFilter())->toBeFalse();
});

it('caps an unreasonably large term list per taxonomy', function () {
    $many = array_map(fn ($i) => "term-{$i}", range(1, 200));

    $state = FilterStateParser::fromArray(['product_cat' => $many]);

    expect(count($state->categorySlugs))->toBeLessThanOrEqual(50);
});

// ── search ───────────────────────────────────────────────────────────────────

it('parses a search term from s', function () {
    expect(FilterStateParser::fromArray(['s' => 'boots'])->search)->toBe('boots');
});

it('treats an empty search string as no search', function () {
    expect(FilterStateParser::fromArray(['s' => ''])->search)->toBeNull();
});

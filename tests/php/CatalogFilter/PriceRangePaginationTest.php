<?php

/**
 * Regression: the AVAILABLE price-slider range must not change with pagination.
 *
 * Drives the real server path that decides what query
 * sobe_get_filtered_price_range() runs for the AJAX `price_range` response:
 *
 *   FilterStateParser::fromArray()
 *     -> FilterHandler::buildQueryArgs()  (QueryTransformer::buildStandaloneQueryArgs
 *                                          + WC catalog-ordering translation)
 *     -> the two sobe/*_query_args apply_filters (no consumers in core)
 *     -> QueryTransformer::priceRangeBaseArgs()   <-- the args the price
 *                                                     lookup's WP_Query gets
 *
 * Every arg-construction line runs for real; only WC()/visibility lookups are
 * stubbed. The contract: for one filter state, the price-lookup args on
 * page 1 == page 2 == page N, carry no page/offset and no catalog-ordering
 * meta_key (which would silently filter the universe), so the resulting
 * MIN/MAX over those ids is identical.
 */

namespace {
    if (! class_exists('WooCommerce')) {
        class WooCommerce {}
    }
}

namespace App\WooCommerce\CatalogFilter {

    use App\WooCommerce\FilterHandler;
    use Brain\Monkey\Functions;

    beforeEach(function () {
        Functions\when('sanitize_key')->alias(fn ($v) => strtolower((string) $v));
        Functions\when('sanitize_title')->alias(fn ($v) => strtolower(trim((string) $v)));
        Functions\when('sanitize_text_field')->alias(fn ($v) => trim((string) $v));
        Functions\when('apply_filters')->alias(fn ($hook, $value = null) => $value);
        Functions\when('add_filter')->justReturn(true);
        Functions\when('taxonomy_exists')->justReturn(true);
        Functions\when('get_option')->justReturn('no');
        Functions\when('wc_get_attribute_taxonomies')->justReturn([]);
        Functions\when('wc_attribute_taxonomy_name')->alias(fn ($n) => 'pa_'.$n);
        Functions\when('wc_get_product_visibility_term_ids')->justReturn([
            'exclude-from-catalog' => 20,
            'exclude-from-search' => 21,
            'outofstock' => 22,
        ]);

        $wcQuery = new class {
            public string $mode = 'menu_order';

            public function get_catalog_ordering_args()
            {
                return match ($this->mode) {
                    'popularity' => ['orderby' => 'meta_value_num', 'order' => 'DESC', 'meta_key' => 'total_sales'],
                    'rating' => ['orderby' => 'meta_value_num', 'order' => 'DESC', 'meta_key' => '_wc_average_rating'],
                    default => ['orderby' => 'menu_order title', 'order' => 'ASC', 'meta_key' => ''],
                };
            }
        };
        $GLOBALS['sobe_wc_query_stub'] = $wcQuery;
        $wc = new class($wcQuery) {
            public function __construct(public $query) {}
        };
        Functions\when('WC')->alias(fn () => $wc);
    });

    afterEach(function () {
        unset($GLOBALS['sobe_wc_query_stub']);
    });

    /**
     * The real chain, up to (and including) the args the price-range WP_Query
     * would be constructed with.
     *
     * @return array<string, mixed>
     */
    function priceLookupArgsForPage(array $state, CatalogContext $context, int $paged): array
    {
        $filterState = FilterStateParser::fromArray($state + ['paged' => $paged]);

        $queryArgs = \invokeMethod(new FilterHandler('sobe'), 'buildQueryArgs', [$filterState, $context, 12]);
        $queryArgs = (array) apply_filters('sobe/catalog_filters/query_args', $queryArgs, $state);
        $queryArgs = (array) apply_filters('sobe/shop_loop/query_args', $queryArgs, ['context' => 'catalog_filters', 'state' => $state]);

        return QueryTransformer::priceRangeBaseArgs($queryArgs);
    }

    /**
     * MIN/MAX over the ids a lookup with these args would select, given a
     * fixed price map. A leftover ordering meta_key => an INNER JOIN on
     * wp_postmeta => never-sold products drop out.
     *
     * @return array{min: float, max: float}
     */
    function simulatedRange(array $lookupArgs, array $priceMap, array $metaPresence): array
    {
        $ids = array_keys($priceMap);
        if (! empty($lookupArgs['meta_key'])) {
            $key = $lookupArgs['meta_key'];
            $ids = array_values(array_filter($ids, fn ($id) => ! empty($metaPresence[$key][$id])));
        }
        $prices = array_map(fn ($id) => $priceMap[$id], $ids);

        return $prices === [] ? ['min' => 0.0, 'max' => 0.0] : ['min' => min($prices), 'max' => max($prices)];
    }

    // The validated context FilterHandler::process() would hold after
    // CatalogContext::fromArray() accepts the posted brand-archive payload
    // (term validation itself is covered by CatalogContextTest).
    function brandArchiveContext(): CatalogContext
    {
        return CatalogContext::taxonomy('product_brand', 'samelin', 7);
    }

    function priceUniverse(): array
    {
        $map = [];
        foreach (array_values(range(1001, 1040)) as $i => $id) {
            $map[$id] = 18.0 + $i * ((347.0 - 18.0) / 39);
        }

        return $map;
    }

    function totalSalesPresence(): array
    {
        return ['total_sales' => array_fill_keys(range(1015, 1024), true)];
    }

    it('feeds an identical, page-free, ordering-free query to the price lookup on pages 1/2/3', function () {
        $state = ['product_brand' => 'samelin', 'filter_pa_color' => 'black'];

        $a1 = priceLookupArgsForPage($state, brandArchiveContext(), 1);
        $a2 = priceLookupArgsForPage($state, brandArchiveContext(), 2);
        $a3 = priceLookupArgsForPage($state, brandArchiveContext(), 3);

        foreach (['1' => $a1, '2' => $a2, '3' => $a3] as $page => $a) {
            expect($a)->not->toHaveKey('paged', "page $page kept paged");
            expect($a)->not->toHaveKey('offset', "page $page kept offset");
            expect($a)->not->toHaveKey('page', "page $page kept page");
            expect($a['posts_per_page'])->toBe(-1);
            expect($a['nopaging'])->toBeTrue();
            expect($a['fields'])->toBe('ids');
        }

        expect($a1)->toEqual($a2);
        expect($a2)->toEqual($a3);
    });

    it('does not inherit the catalog ordering meta_key when the archive is sorted by popularity', function () {
        $GLOBALS['sobe_wc_query_stub']->mode = 'popularity';
        $state = ['product_brand' => 'samelin', 'orderby' => 'popularity'];

        $a1 = priceLookupArgsForPage($state, brandArchiveContext(), 1);
        $a2 = priceLookupArgsForPage($state, brandArchiveContext(), 2);

        expect($a1)->not->toHaveKey('meta_key');
        expect($a1)->not->toHaveKey('orderby');
        expect($a1)->not->toHaveKey('order');
        expect($a1)->toEqual($a2);
    });

    it('returns the full-universe range on every page, sorted by popularity or not', function () {
        $priceMap = priceUniverse();
        $meta = totalSalesPresence();
        $fullMin = min($priceMap);
        $fullMax = max($priceMap);

        $default = simulatedRange(priceLookupArgsForPage(['product_brand' => 'samelin'], brandArchiveContext(), 2), $priceMap, $meta);
        expect($default['min'])->toBe($fullMin);
        expect($default['max'])->toBe($fullMax);

        $GLOBALS['sobe_wc_query_stub']->mode = 'popularity';
        $pop = simulatedRange(
            priceLookupArgsForPage(['product_brand' => 'samelin', 'orderby' => 'popularity'], brandArchiveContext(), 3),
            $priceMap,
            $meta
        );
        expect($pop['min'])->toBe($fullMin);
        expect($pop['max'])->toBe($fullMax);
    });

    it('keeps the price lookup page-free with a price_type filter active', function () {
        Functions\when('wc_get_product_ids_on_sale')->justReturn([1001, 1002, 1003, 1004, 1005]);
        $state = ['product_brand' => 'samelin', 'price_type' => 'on_sale'];

        $a1 = priceLookupArgsForPage($state, brandArchiveContext(), 1);
        $a2 = priceLookupArgsForPage($state, brandArchiveContext(), 2);

        expect($a1)->not->toHaveKey('paged');
        expect($a1)->toEqual($a2);
    });

    it('keeps the price lookup page-free with a min/max price selection also active', function () {
        $state = ['product_brand' => 'samelin', 'min_price' => '80', 'max_price' => '180'];

        $a1 = priceLookupArgsForPage($state, brandArchiveContext(), 1);
        $a2 = priceLookupArgsForPage($state, brandArchiveContext(), 2);

        expect($a1)->not->toHaveKey('paged');
        expect($a1)->not->toHaveKey('sobe_catalog_price_filter');
        expect($a1)->toEqual($a2);
    });
}

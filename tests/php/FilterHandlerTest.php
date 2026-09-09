<?php

/**
 * Unit tests for FilterHandler's own remaining logic — result-count
 * rendering. Query construction (tax_query/meta_query/visibility/price)
 * moved to App\WooCommerce\CatalogFilter\FilterStateParser and
 * QueryTransformer as part of unifying it with the native main query and
 * load-more (see tests/php/CatalogFilter/); that's where the equivalent, now
 * far more thorough, coverage lives — this file only covers what's still
 * FilterHandler's own responsibility.
 */

use App\WooCommerce\FilterHandler;
use Brain\Monkey\Functions;

beforeEach(function () {
    Functions\when('__')->returnArg();
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

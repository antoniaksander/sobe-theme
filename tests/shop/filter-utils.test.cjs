/**
 * Unit tests for resources/js/filter-utils.js
 *
 * These tests encode the behaviour that caused production bugs — run them before
 * any change to the filter URL or state logic.
 */

const {
  activePriceSelection,
  buildFilterUrl,
  hasActiveFilters,
  projectPriceSelection,
  resolveCatalogResultsScope,
  _resetCatalogScopeWarning,
  splitFilterValue,
} = require('../../resources/js/filter-utils.js');

const BASE = 'https://example.com/shop/';

// ── buildFilterUrl ────────────────────────────────────────────────────────────

describe('buildFilterUrl', () => {
  test('page 1 produces no paged param', () => {
    const url = buildFilterUrl({ paged: 1, orderby: 'popularity' }, BASE);
    expect(new URL(url).searchParams.get('paged')).toBeNull();
  });

  test('page 2 produces paged=2 in query string (not fragment)', () => {
    const url = buildFilterUrl({ paged: 2, orderby: 'popularity' }, BASE);
    const parsed = new URL(url);
    expect(parsed.searchParams.get('paged')).toBe('2');
    expect(parsed.hash).toBe('');
  });

  test('page 3 increments correctly after page 2', () => {
    const url = buildFilterUrl({ paged: 3, orderby: 'popularity' }, BASE);
    expect(new URL(url).searchParams.get('paged')).toBe('3');
  });

  test('orderby=popularity is included in URL', () => {
    const url = buildFilterUrl({ orderby: 'popularity' }, BASE);
    expect(new URL(url).searchParams.get('orderby')).toBe('popularity');
  });

  test('orderby=menu_order is omitted (WC default)', () => {
    const url = buildFilterUrl({ orderby: 'menu_order' }, BASE);
    expect(new URL(url).searchParams.get('orderby')).toBeNull();
  });

  test('orderby is preserved alongside paged', () => {
    const url = buildFilterUrl({ paged: 2, orderby: 'popularity' }, BASE);
    const parsed = new URL(url);
    expect(parsed.searchParams.get('orderby')).toBe('popularity');
    expect(parsed.searchParams.get('paged')).toBe('2');
  });

  test('archive taxonomy term is skipped (implicit in path)', () => {
    const url = buildFilterUrl(
      { 'filter_brand': 'nike', orderby: 'popularity' },
      'https://example.com/brand/nike/',
      'filter_brand',
      'nike',
    );
    expect(new URL(url).searchParams.get('filter_brand')).toBeNull();
    expect(new URL(url).searchParams.get('orderby')).toBe('popularity');
  });

  test('additional brand beyond archive term is included as filter param', () => {
    const url = buildFilterUrl(
      { 'filter_brand': ['nike', 'adidas'] },
      'https://example.com/brand/nike/',
      'filter_brand',
      'nike',
    );
    expect(new URL(url).searchParams.get('filter_brand')).toBe('nike,adidas');
  });

  test('attribute filter (array) uses the WooCommerce-canonical comma separator', () => {
    const url = buildFilterUrl({ 'filter_color': ['blue', 'red'] }, BASE);
    expect(new URL(url).searchParams.get('filter_color')).toBe('blue,red');
  });

  test('price range is included when outside slider defaults', () => {
    const url = buildFilterUrl(
      { min_price: '20', max_price: '80' },
      BASE,
      null,
      null,
      { min: 0, max: 100 },
    );
    const parsed = new URL(url);
    expect(parsed.searchParams.get('min_price')).toBe('20');
    expect(parsed.searchParams.get('max_price')).toBe('80');
  });

  test('min_price at slider minimum is omitted', () => {
    const url = buildFilterUrl(
      { min_price: '0', max_price: '80' },
      BASE,
      null,
      null,
      { min: 0, max: 100 },
    );
    expect(new URL(url).searchParams.get('min_price')).toBeNull();
  });

  test('max_price at slider maximum is omitted', () => {
    const url = buildFilterUrl(
      { min_price: '20', max_price: '100' },
      BASE,
      null,
      null,
      { min: 0, max: 100 },
    );
    expect(new URL(url).searchParams.get('max_price')).toBeNull();
  });

  test('price_type=on_sale is included', () => {
    const url = buildFilterUrl({ price_type: 'on_sale' }, BASE);
    expect(new URL(url).searchParams.get('price_type')).toBe('on_sale');
  });

  test('price_type=all is omitted', () => {
    const url = buildFilterUrl({ price_type: 'all' }, BASE);
    expect(new URL(url).searchParams.get('price_type')).toBeNull();
  });

  test('search query s is included', () => {
    const url = buildFilterUrl({ s: 'tote bag' }, BASE);
    expect(new URL(url).searchParams.get('s')).toBe('tote bag');
  });

  test('empty string s is omitted', () => {
    const url = buildFilterUrl({ s: '' }, BASE);
    expect(new URL(url).searchParams.get('s')).toBeNull();
  });
});

describe('buildFilterUrl — available vs selected price range', () => {
  test('a re-scoped available range does not resurrect a price param the thumbs sit on', () => {
    // A non-price filter narrowed the available range to 30–260 and the
    // slider thumbs snapped to those bounds. collectState() carries those
    // exact values; the URL must stay clean.
    const url = buildFilterUrl(
      { filter_color: ['black'], min_price: '30', max_price: '260' },
      BASE,
      null,
      null,
      { min: 30, max: 260 },
    );
    const parsed = new URL(url);
    expect(parsed.searchParams.get('min_price')).toBeNull();
    expect(parsed.searchParams.get('max_price')).toBeNull();
    expect(parsed.searchParams.get('filter_color')).toBe('black');
  });

  test('a genuine selection survives when the available range widens again', () => {
    const url = buildFilterUrl(
      { min_price: '80', max_price: '180' },
      BASE,
      null,
      null,
      { min: 18, max: 347 },
    );
    const parsed = new URL(url);
    expect(parsed.searchParams.get('min_price')).toBe('80');
    expect(parsed.searchParams.get('max_price')).toBe('180');
  });

  test('sub-unit lookup bounds (18.99) are not treated as a selection', () => {
    const url = buildFilterUrl(
      { min_price: '19', max_price: '346' },
      BASE,
      null,
      null,
      { min: 18.99, max: 346.01 },
    );
    const parsed = new URL(url);
    expect(parsed.searchParams.get('min_price')).toBeNull();
    expect(parsed.searchParams.get('max_price')).toBeNull();
  });
});

// ── activePriceSelection ──────────────────────────────────────────────────────

describe('activePriceSelection', () => {
  test('thumbs on the available bounds are not a selection', () => {
    expect(activePriceSelection('18', '347', { min: 18, max: 347 })).toEqual({});
  });

  test('a value strictly inside the range on each side is a selection', () => {
    expect(activePriceSelection('80', '180', { min: 18, max: 347 })).toEqual({
      min_price: '80',
      max_price: '180',
    });
  });

  test('only one side inside the range', () => {
    expect(activePriceSelection('18', '180', { min: 18, max: 347 })).toEqual({ max_price: '180' });
    expect(activePriceSelection('80', '347', { min: 18, max: 347 })).toEqual({ min_price: '80' });
  });

  test('half-step slop around a bound is absorbed', () => {
    expect(activePriceSelection('19', '346', { min: 18.99, max: 346.01 })).toEqual({});
  });

  test('an available range that narrowed past the current thumbs yields no selection', () => {
    // available re-scoped to 100–150; thumbs still report the old 80/180
    expect(activePriceSelection('80', '180', { min: 100, max: 150 })).toEqual({});
  });

  test('missing inputs yield no selection', () => {
    expect(activePriceSelection(undefined, undefined, { min: 0, max: 100 })).toEqual({});
  });
});

// ── projectPriceSelection ─────────────────────────────────────────────────────

describe('projectPriceSelection', () => {
  test('no selection snaps both thumbs to the new available bounds', () => {
    expect(projectPriceSelection({ min: 30, max: 260 }, {})).toEqual({ from: 30, to: 260 });
  });

  test('a selection inside the new range is preserved', () => {
    expect(
      projectPriceSelection({ min: 18, max: 347 }, { min_price: '80', max_price: '180' }),
    ).toEqual({ from: 80, to: 180 });
  });

  test('a selection outside the new range is clamped to it', () => {
    expect(
      projectPriceSelection({ min: 100, max: 150 }, { min_price: '80', max_price: '180' }),
    ).toEqual({ from: 100, to: 150 });
  });

  test('pagination re-sends the same available range and the thumbs do not move', () => {
    const page1 = projectPriceSelection({ min: 18, max: 347 }, { min_price: '80', max_price: '180' });
    const page2 = projectPriceSelection({ min: 18, max: 347 }, { min_price: '80', max_price: '180' });
    expect(page1).toEqual(page2);
  });
});

// ── splitFilterValue ──────────────────────────────────────────────────────────

describe('splitFilterValue', () => {
  test('parses new canonical comma-delimited values', () => {
    expect(splitFilterValue('blue,red')).toEqual(['blue', 'red']);
  });

  test('still parses legacy +-delimited values (backward compatibility)', () => {
    expect(splitFilterValue('blue+red')).toEqual(['blue', 'red']);
  });

  test('a single slug round-trips through both encodings identically', () => {
    expect(splitFilterValue('nike')).toEqual(['nike']);
  });

  test('mixed/malformed separators still degrade to a clean slug list', () => {
    expect(splitFilterValue('blue,+red  green')).toEqual(['blue', 'red', 'green']);
  });

  test('empty/undefined input returns an empty list', () => {
    expect(splitFilterValue(undefined)).toEqual([]);
    expect(splitFilterValue('')).toEqual([]);
  });
});

// ── hasActiveFilters ──────────────────────────────────────────────────────────

describe('hasActiveFilters', () => {
  test('null returns false', () => {
    expect(hasActiveFilters(null)).toBe(false);
  });

  test('empty object returns false', () => {
    expect(hasActiveFilters({})).toBe(false);
  });

  test('paged-only state returns false (not a user filter)', () => {
    expect(hasActiveFilters({ paged: 2 })).toBe(false);
  });

  test('orderby-only state returns false', () => {
    expect(hasActiveFilters({ orderby: 'popularity' })).toBe(false);
  });

  test('s-only state returns false', () => {
    expect(hasActiveFilters({ s: 'tote' })).toBe(false);
  });

  test('attribute filter returns true', () => {
    expect(hasActiveFilters({ 'filter_color': ['blue'] })).toBe(true);
  });

  test('empty attribute array returns false', () => {
    expect(hasActiveFilters({ 'filter_color': [] })).toBe(false);
  });

  test('price filter returns true', () => {
    expect(hasActiveFilters({ min_price: '20' })).toBe(true);
  });

  test('on_sale price_type returns true', () => {
    expect(hasActiveFilters({ price_type: 'on_sale' })).toBe(true);
  });

  test('mixed state with paged and real filter returns true', () => {
    expect(hasActiveFilters({ paged: 2, orderby: 'popularity', filter_color: ['red'] })).toBe(true);
  });
});

// ── resolveCatalogResultsScope ───────────────────────────────────────────────

describe('resolveCatalogResultsScope', () => {
  beforeEach(() => _resetCatalogScopeWarning());

  test('returns the [data-sobe-catalog-results] element when present', () => {
    const scopeEl = { __id: 'results' };
    const root = { querySelector: (s) => (s === '[data-sobe-catalog-results]' ? scopeEl : null) };
    expect(resolveCatalogResultsScope(root)).toBe(scopeEl);
  });

  test('grid lookups run inside the marker, not the first .products on the page', () => {
    const archiveGrid = { __id: 'archive-grid' };
    const carouselGrid = { __id: 'carousel-grid' };
    const scopeEl = {
      querySelector: (s) => ({
        '.products': archiveGrid,
        '[data-pagination]': { __id: 'archive-pagination' },
        '[data-result-count]': { __id: 'archive-count' },
      }[s] ?? null),
    };
    const root = {
      // document-wide, the carousel's .products comes first
      querySelector: (s) => (s === '[data-sobe-catalog-results]' ? scopeEl : (s === '.products' ? carouselGrid : null)),
    };

    const scope = resolveCatalogResultsScope(root);
    expect(scope.querySelector('.products')).toBe(archiveGrid);
    expect(scope.querySelector('.products')).not.toBe(carouselGrid);
    expect(scope.querySelector('[data-pagination]').__id).toBe('archive-pagination');
  });

  test('falls back to root (document-wide) and warns once when no marker exists', () => {
    const warn = jest.spyOn(console, 'warn').mockImplementation(() => {});
    const root = { querySelector: () => null };

    expect(resolveCatalogResultsScope(root)).toBe(root);
    expect(resolveCatalogResultsScope(root)).toBe(root);
    expect(warn).toHaveBeenCalledTimes(1); // one-time latch

    warn.mockRestore();
  });

  test('a Product Grid block instance scopes to its own marker, outside .shop-main', () => {
    const gridBlockResults = { __id: 'product-grid-results' };
    const blockRoot = {
      querySelector: (s) => (s === '[data-sobe-catalog-results]' ? gridBlockResults : null),
    };
    expect(resolveCatalogResultsScope(blockRoot)).toBe(gridBlockResults);
  });

  test('tolerates a null / query-less root', () => {
    expect(resolveCatalogResultsScope(null)).toBeNull();
    expect(resolveCatalogResultsScope({})).toEqual({});
  });
});

/**
 * Unit tests for resources/js/filter-utils.js
 *
 * These tests encode the behaviour that caused production bugs — run them before
 * any change to the filter URL or state logic.
 */

const { buildFilterUrl, hasActiveFilters } = require('../../resources/js/filter-utils.js');

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
    expect(new URL(url).searchParams.get('filter_brand')).toBe('nike+adidas');
  });

  test('attribute filter (array) uses + separator', () => {
    const url = buildFilterUrl({ 'filter_color': ['blue', 'red'] }, BASE);
    expect(new URL(url).searchParams.get('filter_color')).toBe('blue+red');
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

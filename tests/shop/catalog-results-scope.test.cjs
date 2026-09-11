/**
 * @jest-environment jsdom
 *
 * Real-DOM coverage for [data-sobe-catalog-results] scoping: the catalog
 * filter AJAX and load-more code must resolve the product grid, pagination,
 * result count and load-more sentinel inside the owning results container —
 * never document-wide — so an editorial Product Carousel placed above or
 * below the archive (its swiper markup also carries the `products` class) can
 * never receive archive results.
 */

const {
  resolveCatalogResultsScope,
  _resetCatalogScopeWarning,
} = require('../../resources/js/filter-utils.js');

const carouselMarkup = `
  <section class="product-carousel product-carousel--sobe woocommerce">
    <div class="swiper product-carousel-swiper overflow-hidden">
      <ul class="swiper-wrapper products m-0 p-0 !flex">
        <div class="swiper-slide"><li class="product">carousel card 1</li></div>
        <div class="swiper-slide"><li class="product">carousel card 2</li></div>
      </ul>
    </div>
  </section>`;

const archiveResultsMarkup = `
  <div class="shop-main">
    <div data-sobe-catalog-results>
      <p class="woocommerce-result-count" data-result-count>Showing 1&ndash;36 of 78 results</p>
      <ul class="products">
        <li class="product">archive card 1</li>
        <li class="product">archive card 2</li>
      </ul>
      <div data-pagination>
        <nav class="woocommerce-pagination"><a href="?paged=2">2</a></nav>
        <div data-load-more-sentinel data-page="2"></div>
      </div>
    </div>
  </div>`;

function archiveGrid() {
  return document.querySelector('[data-sobe-catalog-results] .products');
}

beforeEach(() => {
  _resetCatalogScopeWarning();
  document.body.innerHTML = '';
});

test('Product Carousel above the archive: results scope resolves to the archive grid', () => {
  document.body.innerHTML = carouselMarkup + archiveResultsMarkup;

  // document-wide, the carousel's .products is first
  expect(document.querySelector('.products').textContent).toContain('carousel');

  const scope = resolveCatalogResultsScope(document);
  expect(scope.querySelector('.products')).toBe(archiveGrid());
  expect(scope.querySelector('.products').textContent).toContain('archive');
});

test('Product Carousel below the archive: still resolves to the archive grid', () => {
  document.body.innerHTML = archiveResultsMarkup + carouselMarkup;

  const scope = resolveCatalogResultsScope(document);
  expect(scope.querySelector('.products')).toBe(archiveGrid());
});

test('normal archive without a carousel: resolves to the only grid', () => {
  document.body.innerHTML = archiveResultsMarkup;

  const scope = resolveCatalogResultsScope(document);
  expect(scope.querySelector('.products')).toBe(archiveGrid());
});

test('pagination and result count resolve inside the marker', () => {
  document.body.innerHTML = carouselMarkup + archiveResultsMarkup;
  const scope = resolveCatalogResultsScope(document);

  expect(scope.querySelector('[data-pagination]')).toBe(
    document.querySelector('[data-sobe-catalog-results] [data-pagination]'),
  );
  expect(scope.querySelector('[data-result-count]')).toBe(
    document.querySelector('[data-sobe-catalog-results] [data-result-count]'),
  );
});

test('load-more sentinel resolves inside the marker', () => {
  document.body.innerHTML = carouselMarkup + archiveResultsMarkup;
  const scope = resolveCatalogResultsScope(document);

  const sentinel = scope.querySelector('[data-pagination] [data-load-more-sentinel]');
  expect(sentinel).not.toBeNull();
  expect(sentinel.closest('[data-sobe-catalog-results]')).not.toBeNull();
});

test('Product Grid block instance: results scope is the block marker, outside .shop-main', () => {
  document.body.innerHTML = `
    ${carouselMarkup}
    <div class="wp-block-roxder-product-grid">
      <div data-sobe-catalog-results>
        <ul class="products"><li class="product">grid block card</li></ul>
        <div data-pagination></div>
      </div>
    </div>`;

  const scope = resolveCatalogResultsScope(document);
  expect(scope.closest('.shop-main')).toBeNull();
  expect(scope.querySelector('.products').textContent).toContain('grid block');
});

test('an un-migrated template (no marker): falls back to document, warns once', () => {
  const warn = jest.spyOn(console, 'warn').mockImplementation(() => {});
  document.body.innerHTML = `<div class="shop-main"><ul class="products"><li class="product">x</li></ul></div>`;

  expect(resolveCatalogResultsScope(document)).toBe(document);
  expect(resolveCatalogResultsScope(document)).toBe(document);
  expect(warn).toHaveBeenCalledTimes(1);

  warn.mockRestore();
});

// ── integration guard: the catalog code paths use the helper ────────────────

const { readFileSync } = require('node:fs');
const { resolve } = require('node:path');
const read = (p) => readFileSync(resolve(__dirname, '../..', p), 'utf8');

test('catalog-filters view.js resolves results through resolveCatalogResultsScope', () => {
  const src = read('resources/blocks/sobe/catalog-filters/view.js');
  expect(src).toMatch(/resolveCatalogResultsScope/);
  // no bare document-wide grid lookup in the AJAX path
  expect(src).not.toMatch(/document\.querySelector\(\s*['"]\.products['"]\s*\)/);
});

test('shop-load-more.js resolves grid/pagination through resolveCatalogResultsScope', () => {
  const src = read('resources/js/shop-load-more.js');
  expect(src).toMatch(/resolveCatalogResultsScope/);
  expect(src).not.toMatch(/document\.querySelector\(\s*['"]\.woocommerce ul\.products['"]\s*\)/);
});

test('the archive template renders the results marker', () => {
  const src = read('resources/views/woocommerce/archive-product.blade.php');
  expect(src).toMatch(/<div data-sobe-catalog-results>/);
  // marker wraps the grid + pagination, inside .shop-main
  const afterMarker = src.slice(src.indexOf('data-sobe-catalog-results'));
  expect(afterMarker).toMatch(/woocommerce_product_loop_start/);
  expect(afterMarker).toMatch(/data-pagination/);
});

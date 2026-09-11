/**
 * Pure utility functions shared between catalog-filters/view.js and shop-load-more.js.
 * Exported as ES module so they can be unit-tested with Jest without a browser.
 */

/** Attribute marking the container that owns one catalog instance's results. */
export const CATALOG_RESULTS_SCOPE_SELECTOR = '[data-sobe-catalog-results]';

let scopeFallbackWarned = false;

/**
 * Resolve the element that owns a catalog instance's results — its product
 * grid, pagination, result count and load-more sentinel.
 *
 * The archive/shop/search template (and any filterable Product Grid instance)
 * wraps exactly those nodes in `[data-sobe-catalog-results]`. Resolving inside
 * that marker means AJAX result HTML can never be injected into an unrelated
 * `.products` element on the page — e.g. an editorial Product Carousel placed
 * above or below the catalog, whose `.swiper-wrapper`/per-slide markup also
 * carries the `products` class.
 *
 * Falls back to `root` (document-wide, the pre-marker behaviour) when no
 * marker is present, so a client's un-migrated overridden template keeps
 * working; it warns once so the gap is visible.
 *
 * @param {Document|Element|null} [root=document]
 * @returns {Document|Element}
 */
export function resolveCatalogResultsScope(root = (typeof document !== 'undefined' ? document : null)) {
  if (!root || typeof root.querySelector !== 'function') return root;

  const scope = root.querySelector(CATALOG_RESULTS_SCOPE_SELECTOR);
  if (scope) return scope;

  if (!scopeFallbackWarned && typeof console !== 'undefined') {
    scopeFallbackWarned = true;
    console.warn(
      `[sobe catalog-filters] No ${CATALOG_RESULTS_SCOPE_SELECTOR} found — using document-wide result lookups. `
      + 'Wrap the catalog grid, pagination and result count in that container.',
    );
  }

  return root;
}

/** Test-only: reset the one-time fallback warning latch. */
export function _resetCatalogScopeWarning() {
  scopeFallbackWarned = false;
}

/**
 * Half a slider step. A price input sitting within this distance of an
 * available bound is treated as "no selection on that side", not a filter —
 * this absorbs the sub-unit difference between an integer-rounded slider
 * thumb and a wc_product_meta_lookup bound like 18.99.
 */
export const PRICE_BOUND_EPSILON = 0.5;

/**
 * Decide which price bounds are an explicit user selection, given the range
 * the slider currently makes available.
 *
 * The price inputs ALWAYS hold a value (the slider seeds them from its
 * bounds), so "there is a number in the box" can't mean "the user filtered
 * by price". Only a value strictly inside the available range does. This is
 * the single source of truth for that decision — buildFilterUrl(),
 * collectState() and the Clear-all/Reset-price affordances all defer to it,
 * so the canonical URL, the AJAX request and the slider can't disagree about
 * whether a price filter is active.
 *
 * @param {string|number|null|undefined} minValue   Current [data-price-min] value
 * @param {string|number|null|undefined} maxValue   Current [data-price-max] value
 * @param {{min: number|string, max: number|string}} available  Available range (slider data-min/data-max)
 * @returns {{min_price?: string, max_price?: string}}
 */
export function activePriceSelection(minValue, maxValue, available = {}) {
  const selection = {};

  const availMin = parseFloat(available.min);
  const availMax = parseFloat(available.max);
  const min = parseFloat(minValue);
  const max = parseFloat(maxValue);

  if (Number.isFinite(min) && (!Number.isFinite(availMin) || min > availMin + PRICE_BOUND_EPSILON)) {
    selection.min_price = String(minValue);
  }
  if (Number.isFinite(max) && (!Number.isFinite(availMax) || max < availMax - PRICE_BOUND_EPSILON)) {
    selection.max_price = String(maxValue);
  }

  return selection;
}

/**
 * Project a price selection onto a (possibly re-scoped) available range,
 * returning the slider thumb positions to show.
 *
 * - No selection on a side  → that thumb snaps to the new available bound.
 * - A real selection        → that thumb is clamped into the new bounds.
 *
 * It never invents a selection: a non-price filter that re-scopes the
 * available range moves the thumbs but does not, by itself, create a
 * min_price/max_price constraint (that only happens if the projected value
 * still lands strictly inside the new range and the caller re-reads it
 * through activePriceSelection()).
 *
 * @param {{min: number|string, max: number|string}} available  New available range
 * @param {{min_price?: string|number, max_price?: string|number}} selection  Active selection, if any
 * @returns {{from: number, to: number}}
 */
export function projectPriceSelection(available = {}, selection = {}) {
  const min = parseFloat(available.min);
  const max = parseFloat(available.max);
  const reqMin = parseFloat(selection.min_price);
  const reqMax = parseFloat(selection.max_price);

  const clamp = (value) => Math.min(Math.max(value, min), max);

  return {
    from: Number.isFinite(reqMin) ? clamp(reqMin) : min,
    to: Number.isFinite(reqMax) ? clamp(reqMax) : max,
  };
}

/**
 * Build a canonical filter URL from a state object.
 *
 * @param {object} state           Active filter state (keys: paged, orderby, filter_*, …)
 * @param {string} pageBase        Origin + pathname of the current page (no trailing slash or query)
 * @param {string|null} archiveKey Taxonomy key that is implicit in the URL path (skip as filter param)
 * @param {string|null} archiveTerm Term slug that is the current archive page (skip as filter param)
 * @param {object} sliderDefaults  { min: number, max: number } — price slider AVAILABLE range
 * @returns {string}
 */
export function buildFilterUrl(state, pageBase, archiveKey = null, archiveTerm = null, sliderDefaults = { min: 0, max: Infinity }) {
  const url = new URL(pageBase);

  // Resolve price against the available range once, up front, so a thumb
  // parked on a (possibly re-scoped) bound never reaches the URL.
  const price = activePriceSelection(state.min_price, state.max_price, sliderDefaults);

  for (const [key, val] of Object.entries(state)) {
    if (key === 'paged') continue;
    if (key === 'min_price' || key === 'max_price') continue;

    if (key === archiveKey) {
      const slugs = Array.isArray(val) ? val : [val];
      if (slugs.length === 1 && slugs[0] === archiveTerm) continue;
      // Comma is the WooCommerce-canonical multi-value separator (matches
      // native layered nav's own filter_{attribute}=a,b convention) — new
      // URLs use it; splitFilterValue() below still reads legacy '+' URLs.
      url.searchParams.set('filter_' + key.replace(/^filter_/, ''), slugs.join(','));
      continue;
    }

    if (key === 'orderby') {
      if (val && val !== 'menu_order') url.searchParams.set('orderby', val);
      continue;
    }

    if (key === 's') {
      if (val) url.searchParams.set('s', val);
      continue;
    }

    if (key === 'price_type') {
      if (val && val !== 'all') url.searchParams.set('price_type', val);
      continue;
    }

    if (Array.isArray(val)) {
      url.searchParams.set('filter_' + key.replace(/^filter_/, ''), val.join(','));
    } else if (val !== '' && val !== null && val !== undefined) {
      url.searchParams.set(key, val);
    }
  }

  if (price.min_price !== undefined) url.searchParams.set('min_price', price.min_price);
  if (price.max_price !== undefined) url.searchParams.set('max_price', price.max_price);

  const page = parseInt(state.paged, 10) || 1;
  if (page > 1) url.searchParams.set('paged', String(page));

  return url.toString();
}

/**
 * Split a filter value read from a URL back into taxonomy slugs.
 *
 * New URLs use ',' (WooCommerce-canonical, matches native layered nav).
 * Legacy Sobe URLs used '+' and must keep parsing the same way indefinitely —
 * this function is the one place accepting both, so a bookmarked/shared
 * legacy URL and a newly-generated one produce identical filter state.
 *
 * @param {string|null|undefined} value
 * @returns {string[]}
 */
export function splitFilterValue(value) {
  return String(value ?? '')
    .split(/[+,\s]+/)
    .filter(Boolean);
}

/**
 * Returns true when the filter state contains meaningful user-applied filters.
 * Excludes paged / orderby / s — those are not "filters" from the user's POV.
 *
 * @param {object|null} filterState
 * @returns {boolean}
 */
export function hasActiveFilters(filterState) {
  if (!filterState) return false;
  return Object.keys(filterState).some((k) => {
    if (k === 'paged' || k === 'orderby' || k === 's') return false;
    const v = filterState[k];
    return Array.isArray(v) ? v.length > 0 : !!v;
  });
}

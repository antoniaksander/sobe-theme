/**
 * Pure utility functions shared between catalog-filters/view.js and shop-load-more.js.
 * Exported as ES module so they can be unit-tested with Jest without a browser.
 */

/**
 * Build a canonical filter URL from a state object.
 *
 * @param {object} state           Active filter state (keys: paged, orderby, filter_*, …)
 * @param {string} pageBase        Origin + pathname of the current page (no trailing slash or query)
 * @param {string|null} archiveKey Taxonomy key that is implicit in the URL path (skip as filter param)
 * @param {string|null} archiveTerm Term slug that is the current archive page (skip as filter param)
 * @param {object} sliderDefaults  { min: number, max: number } — price slider range
 * @returns {string}
 */
export function buildFilterUrl(state, pageBase, archiveKey = null, archiveTerm = null, sliderDefaults = { min: 0, max: Infinity }) {
  const url = new URL(pageBase);

  for (const [key, val] of Object.entries(state)) {
    if (key === 'paged') continue;

    if (key === archiveKey) {
      const slugs = Array.isArray(val) ? val : [val];
      if (slugs.length === 1 && slugs[0] === archiveTerm) continue;
      url.searchParams.set('filter_' + key.replace(/^filter_/, ''), slugs.join('+'));
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

    if (key === 'min_price') {
      if (parseFloat(val) > sliderDefaults.min) url.searchParams.set('min_price', val);
      continue;
    }

    if (key === 'max_price') {
      if (parseFloat(val) < sliderDefaults.max) url.searchParams.set('max_price', val);
      continue;
    }

    if (Array.isArray(val)) {
      url.searchParams.set('filter_' + key.replace(/^filter_/, ''), val.join('+'));
    } else if (val !== '' && val !== null && val !== undefined) {
      url.searchParams.set(key, val);
    }
  }

  const page = parseInt(state.paged, 10) || 1;
  if (page > 1) url.searchParams.set('paged', String(page));

  return url.toString();
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

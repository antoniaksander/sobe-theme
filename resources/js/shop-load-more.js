import { hasActiveFilters } from './filter-utils.js';
import { getState, getAction, getNonce } from './filter-store.js';

(function () {
  const params = window.sobeLoadMoreParams;
  if (!params) return;

  let sentinel = document.querySelector('[data-load-more-sentinel]');
  if (!sentinel) return;

  let loading = false;

  const observer = new IntersectionObserver(
    (entries) => { if (entries[0].isIntersecting && !loading) loadMore(); },
    { rootMargin: '200px' },
  );
  observer.observe(sentinel);

  // Re-observe when catalog-filters replaces the pagination zone via AJAX.
  // State sync is handled by the filter store — nothing to read from the event.
  document.addEventListener('sobe:pagination-updated', () => {
    const newSentinel = document.querySelector('[data-pagination] [data-load-more-sentinel]');
    if (newSentinel && newSentinel !== sentinel) {
      observer.unobserve(sentinel);
      sentinel = newSentinel;
      observer.observe(sentinel);
      loading = false;
    }
  });

  async function loadMore() {
    loading = true;
    const page = parseInt(sentinel.dataset.page, 10);
    const paginationZone = document.querySelector('[data-pagination]');
    const grid = document.querySelector('.woocommerce ul.products');

    // Read from the store at call time — not captured at IIFE init — so we
    // always have the state that was active when the user scrolled to the sentinel.
    const filterState  = getState();
    const filterAction = getAction();
    const filterNonce  = getNonce();

    try {
      if (hasActiveFilters(filterState)) {
        const body = new FormData();
        body.append('action', filterAction);
        body.append('nonce', filterNonce);
        body.append('filter_state', JSON.stringify({ ...filterState, paged: page }));
        const res = await fetch(params.ajaxUrl, { method: 'POST', body });
        const data = await res.json();

        if (data.html && grid) grid.insertAdjacentHTML('beforeend', data.html);

        if (paginationZone && data.pagination_html !== undefined) {
          paginationZone.innerHTML = data.pagination_html;
          const newSentinel = paginationZone.querySelector('[data-load-more-sentinel]');
          if (newSentinel) {
            observer.unobserve(sentinel);
            sentinel = newSentinel;
            observer.observe(sentinel);
            loading = false;
          } else {
            observer.disconnect();
          }
        }
        return;
      }

      const body = new FormData();
      body.append('action', params.ajaxAction);
      body.append('nonce', params.nonce);
      body.append('page', page);
      body.append('taxonomy', params.taxonomy ?? '');
      body.append('term_id', params.termId ?? 0);
      body.append('search', params.search ?? '');
      body.append('orderby', params.orderby ?? 'menu_order');
      const res = await fetch(params.ajaxUrl, { method: 'POST', body });
      const data = await res.json();

      if (data.html && grid) grid.insertAdjacentHTML('beforeend', data.html);

      if (data.has_more) {
        sentinel.dataset.page = data.next_page;
        if (params.historyEnabled) {
          history.replaceState({}, '', '?paged=' + data.next_page);
        }
        loading = false;
      } else {
        observer.disconnect();
        sentinel.remove();
      }
    } catch (err) {
      loading = false;
      console.error('[sobe load-more]', err);
      // Show a user-visible message inside the sentinel so the failure is obvious
      // without a full-page reload. Sentinel stays in DOM so scroll does not re-fire.
      const btn = sentinel.querySelector('button');
      if (btn) {
        btn.textContent = params.errorText ?? 'Failed to load. Please refresh.';
        btn.className = 'sobe-load-more-error';
        btn.removeAttribute('aria-live');
      }
    }
  }
})();

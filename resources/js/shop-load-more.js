import { hasActiveFilters } from './filter-utils.js';
import { getState, getAction, getNonce } from './filter-store.js';
import { readParams, isCurrentContext } from './dom-params.js';
import { registerReinit } from './sobe-reinit.js';

const instances = new WeakMap();
const activeInstances = new Set();
const sentinelSelector = '[data-load-more-sentinel]';

function findSentinel(root = document) {
  if (root.nodeType === Node.ELEMENT_NODE && root.matches(sentinelSelector)) {
    return root;
  }

  return root.querySelector?.(sentinelSelector) ?? null;
}

function updateSentinel(state, nextSentinel) {
  if (!nextSentinel || nextSentinel === state.sentinel) return;

  if (state.sentinel) {
    state.observer.unobserve(state.sentinel);
    instances.delete(state.sentinel);
  }

  state.sentinel = nextSentinel;
  instances.set(nextSentinel, state);
  state.observer.observe(nextSentinel);
  state.loading = false;
}

function makeObserver(state) {
  return new IntersectionObserver(
    (entries) => {
      if (entries[0].isIntersecting && !state.loading) {
        loadMore(state);
      }
    },
    { rootMargin: '200px' },
  );
}

async function fetchJson(state, body) {
  state.fetchController?.abort();
  state.fetchController = new AbortController();
  const { signal } = state.fetchController;
  const res = await fetch(state.params.ajaxUrl, { method: 'POST', body, signal });
  const data = await res.json();

  if (signal.aborted || state.destroyed) {
    return null;
  }

  return data;
}

async function loadMore(state) {
  state.loading = true;
  const { sentinel, params, observer } = state;
  const page = parseInt(sentinel.dataset.page, 10);
  const paginationZone = document.querySelector('[data-pagination]');
  const grid = document.querySelector('.woocommerce ul.products');

  const filterState  = getState();
  const filterAction = getAction();
  const filterNonce  = getNonce();

  try {
    if (hasActiveFilters(filterState)) {
      const body = new FormData();
      body.append('action', filterAction);
      body.append('nonce', filterNonce);
      body.append('filter_state', JSON.stringify({ ...filterState, paged: page }));
      const data = await fetchJson(state, body);
      if (!data) return;

      if (data.html && grid) grid.insertAdjacentHTML('beforeend', data.html);

      if (paginationZone && data.pagination_html !== undefined) {
        paginationZone.innerHTML = data.pagination_html;
        const newSentinel = paginationZone.querySelector(sentinelSelector);
        if (newSentinel) {
          updateSentinel(state, newSentinel);
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
    const data = await fetchJson(state, body);
    if (!data) return;

    if (data.html && grid) grid.insertAdjacentHTML('beforeend', data.html);

    if (data.has_more) {
      sentinel.dataset.page = data.next_page;
      if (params.historyEnabled) {
        history.replaceState({}, '', '?paged=' + data.next_page);
      }
      state.loading = false;
    } else {
      observer.disconnect();
      instances.delete(sentinel);
      sentinel.remove();
      state.sentinel = null;
    }
  } catch (err) {
    if (err.name === 'AbortError') return;

    state.loading = false;
    console.error('[sobe load-more]', err);
    const btn = state.sentinel?.querySelector('button');
    if (btn) {
      btn.textContent = params.errorText ?? 'Failed to load. Please refresh.';
      btn.className = 'sobe-load-more-error';
      btn.removeAttribute('aria-live');
    }
  }
}

function init(root = document) {
  const sentinel = findSentinel(root);
  if (!sentinel || instances.has(sentinel)) return;

  const params = readParams(root, 'load-more', window.sobeLoadMoreParams);
  if (!params || !isCurrentContext(params, 'load-more')) return;

  const listenerController = new AbortController();
  const state = {
    destroyed: false,
    fetchController: null,
    listenerController,
    loading: false,
    observer: null,
    params,
    sentinel,
  };

  state.observer = makeObserver(state);
  state.observer.observe(sentinel);
  instances.set(sentinel, state);
  activeInstances.add(state);

  document.addEventListener('sobe:pagination-updated', () => {
    const newSentinel = document.querySelector('[data-pagination] [data-load-more-sentinel]');
    updateSentinel(state, newSentinel);
  }, { signal: listenerController.signal });
}

function destroy() {
  activeInstances.forEach((state) => {
    state.destroyed = true;
    state.listenerController.abort();
    state.fetchController?.abort();
    state.observer.disconnect();
    if (state.sentinel) {
      instances.delete(state.sentinel);
    }
    activeInstances.delete(state);
  });
}

registerReinit('shop-load-more', { init, destroy });

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => init(document));
} else {
  init(document);
}

export { init, destroy };

import noUiSlider from 'nouislider';
import { buildFilterUrl, splitFilterValue } from '../../js/filter-utils.js';
import { commit as commitFilterStore, reset as resetFilterStore } from '../../js/filter-store.js';
import { readParams, isCurrentContext } from '../../js/dom-params.js';
import { registerReinit } from '../../js/sobe-reinit.js';

const instances = new WeakMap();
const activeStates = new Set();
const blockSelector = '[data-catalog-filters-instance]';
const DEBOUNCE_CHECKBOX = 300;
const DEBOUNCE_PRICE = 500;
const LG_BREAKPOINT = 768;

let activeController = null;
let paginationController = null;

function getInstances(root = document) {
  const blocks = [...(root.querySelectorAll?.(blockSelector) || [])];
  if (root.nodeType === Node.ELEMENT_NODE && root.matches(blockSelector)) {
    blocks.unshift(root);
  }
  return blocks;
}

function restoreElement(element, parent, nextSibling) {
  if (!element) return;

  if (parent?.isConnected) {
    parent.insertBefore(element, nextSibling?.parentNode === parent ? nextSibling : null);
    return;
  }

  element.remove();
}

function getFocusable(container) {
  return [
    ...container.querySelectorAll(
      [
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
      ].join(', ')
    ),
  ].filter((el) => {
    if (el.closest('[hidden], [inert], [aria-hidden="true"]')) return false;
    const style = window.getComputedStyle(el);
    return style.visibility !== 'hidden' && style.display !== 'none';
  });
}

function debounce(state, fn, ms) {
  let timer = null;

  return (...args) => {
    if (timer) {
      clearTimeout(timer);
      state.timers.delete(timer);
    }

    timer = setTimeout(() => {
      state.timers.delete(timer);
      timer = null;
      fn(...args);
    }, ms);
    state.timers.add(timer);
  };
}

async function fetchFiltered(state, filterState) {
  state.fetchController?.abort();
  state.fetchController = new AbortController();

  const body = new FormData();
  body.append('action', state.params.action);
  body.append('nonce', state.params.nonce);
  body.append('filter_state', JSON.stringify(filterState));
  body.append('filter_context', JSON.stringify({
    contextType: state.params.contextType ?? '',
    archiveTaxonomy: state.params.archiveTaxonomy ?? '',
    archiveTerm: state.params.archiveTerm ?? '',
  }));

  const res = await fetch(state.params.ajaxUrl, {
    method: 'POST',
    body,
    signal: state.fetchController.signal,
  });
  if (!res.ok) throw new Error(res.status);
  return res.json();
}

function ensurePaginationListener() {
  if (paginationController) return;

  paginationController = new AbortController();

  document.addEventListener('click', (e) => {
    const link = e.target.closest('[data-pagination] a');
    if (!link) return;
    // Navigation-mode contexts paginate via the link itself (the host block
    // renders correct per-page results server-side); don't hijack with AJAX.
    if (link.closest('[data-catalog-filters-navigate]')) return;
    e.preventDefault();

    const href = new URL(link.href);
    let paged = href.searchParams.get('paged');

    if (!paged) {
      const match = href.pathname.match(/\/page\/(\d+)/);
      paged = match ? match[1] : '1';
    }

    const controller = activeController ?? activeStates.values().next().value;
    controller?.applyFilters({ ...controller.collectState(), paged: parseInt(paged, 10) });
  }, { signal: paginationController.signal });
}

function initCatalogFilters(instance, params) {
  const root = instance.querySelector('[data-catalog-filters]');
  const desktopContainer = instance.querySelector('[data-catalog-filters-desktop]');
  const drawer = instance.querySelector('[data-catalog-filters-drawer]');
  const drawerBody = instance.querySelector('[data-catalog-filters-drawer-body]');
  const openBtn = instance.querySelector('[data-catalog-filters-open]');
  const closeButtons = [...instance.querySelectorAll('[data-catalog-filters-close]')];
  const clearAllBtn = root?.querySelector('[data-clear-all-filters]');
  const triggerSlot = document.querySelector('[data-catalog-filters-trigger-slot]');
  const widgetShell = instance.closest('.shop-sidebar .widget');

  if (!root || !desktopContainer || !drawer || !drawerBody || !openBtn) {
    return null;
  }

  const listenerController = new AbortController();
  const { signal } = listenerController;
  const archiveKey = params.archiveTaxonomy?.startsWith('pa_')
    ? 'filter_' + params.archiveTaxonomy.slice(3)
    : params.archiveTaxonomy;
  const state = {
    active: false,
    archiveKey,
    // Navigation mode: the host context (e.g. a product block with its own
    // custom query) can't be served by the shop AJAX handler, so filter and
    // pagination changes load the canonical filter URL instead of AJAX-swapping.
    navigate: !!instance.closest('[data-catalog-filters-navigate]'),
    desktopContainer,
    drawer,
    drawerBody,
    fetchController: null,
    fetchSeq: 0,
    gridCtx: null,
    initSearch: new URLSearchParams(location.search),
    instance,
    listenerController,
    noUiSliders: [],
    openBtn,
    original: {
      drawerNext: drawer.nextSibling,
      drawerParent: drawer.parentNode,
      openBtnNext: openBtn.nextSibling,
      openBtnParent: openBtn.parentNode,
      rootNext: root.nextSibling,
      rootParent: root.parentNode,
      widgetHidden: widgetShell?.hidden ?? null,
    },
    pageBase: location.origin + location.pathname,
    params,
    resizeObserver: null,
    root,
    timers: new Set(),
    triggerHome: document.createComment('catalog filters trigger home'),
    widgetShell,
  };

  openBtn.after(state.triggerHome);

  if (drawer.parentElement !== document.body) {
    drawer.style.setProperty(
      '--filter-drawer-width',
      getComputedStyle(instance).getPropertyValue('--filter-drawer-width').trim() || '320px'
    );
    document.body.appendChild(drawer);
  }

  openBtn.hidden = false;

  let lastOpener = null;

  function setActive() {
    activeController = state;
  }

  function collectState() {
    const filterState = {};

    root.querySelectorAll('input[type="radio"]:checked').forEach((el) => {
      filterState[el.name] = el.value;
    });

    root.querySelectorAll('input[type="checkbox"]:checked').forEach((el) => {
      const name = el.name.replace(/\[\]$/, '');
      if (!filterState[name]) filterState[name] = [];
      filterState[name].push(el.value);
    });

    const minInput = root.querySelector('[data-price-min]');
    const maxInput = root.querySelector('[data-price-max]');
    if (minInput) filterState.min_price = minInput.value;
    if (maxInput) filterState.max_price = maxInput.value;

    root.querySelectorAll('[data-filter-select]').forEach((el) => {
      if (el.value && el.value !== 'all') {
        filterState[el.dataset.filterSelect] = el.value;
      }
    });

    if (params.archiveTaxonomy && params.archiveTerm) {
      if (!filterState[archiveKey] || (Array.isArray(filterState[archiveKey]) && filterState[archiveKey].length === 0)) {
        filterState[archiveKey] = params.archiveTerm;
      }
    }

    const wcOrderSelect = document.querySelector('.woocommerce-ordering select[name="orderby"]');
    const orderby = wcOrderSelect?.value || state.initSearch.get('orderby');
    if (orderby) filterState.orderby = orderby;

    const searchQuery = state.initSearch.get('s');
    if (searchQuery) filterState.s = searchQuery;

    return filterState;
  }

  function buildUrl(filterState) {
    const sliderEl = root.querySelector('[data-range-slider]');
    return buildFilterUrl(
      filterState,
      state.pageBase,
      archiveKey,
      params.archiveTerm ?? null,
      {
        min: parseFloat(sliderEl?.dataset.min ?? 0),
        max: parseFloat(sliderEl?.dataset.max ?? Infinity),
      }
    );
  }

  function updateClearAllVisibility(filterState) {
    if (!clearAllBtn) return;
    const sliderEl = root.querySelector('[data-range-slider]');
    const hasActive = Object.keys(filterState).some((key) => {
      const value = filterState[key];
      if (key === archiveKey && value === params.archiveTerm) return false;
      if (key === 'paged' || key === 'orderby' || key === 's') return false;
      if (key === 'min_price') {
        const defaultMin = parseFloat(sliderEl?.dataset.min ?? 0);
        return parseFloat(value) > defaultMin;
      }
      if (key === 'max_price') {
        const defaultMax = parseFloat(sliderEl?.dataset.max ?? Infinity);
        return parseFloat(value) < defaultMax;
      }
      if (key === 'price_type') return value !== 'all';
      return Array.isArray(value) ? value.length > 0 : !!value;
    });
    clearAllBtn.hidden = !hasActive;
  }

  function updateFilterCounts(data) {
    if (!data?.filters) return;
    const { categories, brands, attributes } = data.filters;

    function patchGroup(terms, getInputFn, listSelector) {
      const groupEl = root.querySelector(listSelector);
      const groupContainer = groupEl?.closest('details');
      terms?.forEach(({ slug, count }) => {
        const input = getInputFn(slug);
        if (!input) return;
        const li = input.closest('li');
        if (!li) return;
        li.hidden = count === 0;
        if (!li.hidden) {
          const badge = li.querySelector('.sobe-filter-count');
          if (badge) badge.textContent = `(${count})`;
        }
      });
      if (groupContainer) {
        const visibleTerms = groupContainer.querySelectorAll('li:not([hidden])');
        groupContainer.hidden = visibleTerms.length === 0;
      }
    }

    patchGroup(
      categories,
      (slug) => root.querySelector(`input[name="product_cat"][value="${slug}"]`),
      '[data-filter-list="categories"]'
    );

    patchGroup(
      brands,
      (slug) => root.querySelector(`[data-filter-list="brands"] input[value="${slug}"]`),
      '[data-filter-list="brands"]'
    );

    if (attributes) {
      for (const [attrName, terms] of Object.entries(attributes)) {
        patchGroup(
          terms,
          (slug) => root.querySelector(`input[name="filter_${attrName}[]"][value="${slug}"]`),
          `[data-filter-list="pa_${attrName}"]`
        );
      }
    }
  }

  function updatePriceRange(data, filterState) {
    const range = data?.price_range;
    const sliderEl = root.querySelector('[data-range-slider]');
    if (!range || !sliderEl?.noUiSlider) return;

    const nextMin = parseFloat(range.min);
    const nextMax = parseFloat(range.max);
    if (!Number.isFinite(nextMin) || !Number.isFinite(nextMax) || nextMax <= nextMin) return;

    const oldMin = parseFloat(sliderEl.dataset.min ?? nextMin);
    const oldMax = parseFloat(sliderEl.dataset.max ?? nextMax);
    const requestedMin = parseFloat(filterState.min_price);
    const requestedMax = parseFloat(filterState.max_price);
    const minWasDefault = !Number.isFinite(requestedMin) || requestedMin <= oldMin;
    const maxWasDefault = !Number.isFinite(requestedMax) || requestedMax >= oldMax;
    const nextFrom = minWasDefault ? nextMin : Math.min(Math.max(requestedMin, nextMin), nextMax);
    const nextTo = maxWasDefault ? nextMax : Math.min(Math.max(requestedMax, nextMin), nextMax);
    const minInput = root.querySelector('[data-price-min]');
    const maxInput = root.querySelector('[data-price-max]');

    sliderEl.dataset.min = String(nextMin);
    sliderEl.dataset.max = String(nextMax);
    sliderEl.dataset.from = String(nextFrom);
    sliderEl.dataset.to = String(nextTo);

    [minInput, maxInput].forEach((input) => {
      input?.setAttribute('min', String(nextMin));
      input?.setAttribute('max', String(nextMax));
    });

    sliderEl.noUiSlider.updateOptions({
      range: { min: nextMin, max: nextMax },
    }, false);
    sliderEl.noUiSlider.set([nextFrom, nextTo]);
  }

  function syncChips(filterState) {
    const zone = root.querySelector('[data-filter-chips]');
    if (!zone) return;
    zone.innerHTML = '';

    for (const [key, val] of Object.entries(filterState)) {
      if (key === archiveKey && val === params.archiveTerm) continue;
      if (key === 'min_price' || key === 'max_price') continue;
      if (key === 'price_type' && val === 'all') continue;
      if (key === 'paged' || key === 'orderby' || key === 's') continue;
      const values = Array.isArray(val) ? val : [val];
      values.forEach((value) => {
        if (!value) return;
        const btn = document.createElement('button');
        btn.className = 'sobe-filter-chip';
        btn.dataset.removeFilter = key;
        btn.dataset.removeValue = value;
        btn.setAttribute('aria-label', `${params.removeLabel} ${value}`);
        btn.innerHTML = `${value} <span class="sobe-filter-chip__remove" aria-hidden="true">${params.removeSymbol}</span>`;
        zone.appendChild(btn);
      });
    }
  }

  async function applyFilters(filterState) {
    if (state.navigate) {
      window.location.assign(buildUrl(filterState));
      return;
    }
    setActive();
    const mySeq = ++state.fetchSeq;

    const grid = document.querySelector('.products');
    const paginationZone = document.querySelector('[data-pagination]');
    const countEl = document.querySelector('[data-result-count]');

    try {
      const data = await fetchFiltered(state, filterState);

      if (state.listenerController.signal.aborted || mySeq !== state.fetchSeq) return;

      if (data.success === false) {
        throw new Error(data.data?.message ?? params.errorText);
      }

      if (grid && data.html !== undefined) {
        state.gridCtx?.revert();
        grid.innerHTML = data.html;
        state.gridCtx = window.gsap?.context(() => {
          window.initAnimationBus?.(grid);
        }, grid);
        window.ScrollTrigger?.refresh();
      }

      if (paginationZone && data.pagination_html !== undefined) {
        paginationZone.innerHTML = data.pagination_html;
        document.dispatchEvent(new CustomEvent('sobe:pagination-updated', {
          detail: { state: filterState, filterAction: params.action, filterNonce: params.nonce },
        }));
      }

      if (countEl && data.count_html !== undefined) {
        countEl.outerHTML = data.count_html;
      }

      commitFilterStore(filterState, params.action, params.nonce);
      syncChips(filterState);
      if (data.filters) updateFilterCounts(data);
      updatePriceRange(data, filterState);
      updateClearAllVisibility(filterState);

      // When Swup is active, preserve its history state structure so back-nav over
      // filter URLs triggers a Swup visit. Without source: 'swup', Swup's default
      // skipPopStateHandling will ignore the popstate event and the DOM won't update.
      const filterUrl = buildUrl(filterState);
      const swupState = window.sobeSwup
        ? { ...(history.state ?? {}), source: 'swup', url: filterUrl }
        : (history.state ?? {});
      history.pushState(swupState, '', filterUrl);

      const shopMain = document.querySelector('.shop-main');
      const target = shopMain ?? grid;
      if (target) {
        const y = target.getBoundingClientRect().top + window.scrollY;
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.scrollTo({ top: Math.max(0, y - 24), behavior: reducedMotion ? 'instant' : 'smooth' });
      }

      if (drawer.hidden) {
        const newCountEl = document.querySelector('[data-result-count]');
        newCountEl?.focus({ preventScroll: true });
      } else if (!drawer.contains(document.activeElement)) {
        getFocusable(drawer)[0]?.focus({ preventScroll: true });
      }
    } catch (err) {
      if (err.name === 'AbortError') return;
      console.error('[sobe catalog-filters]', err);
      if (paginationZone) {
        paginationZone.innerHTML =
          `<p class="sobe-filter-error" role="alert">${params.errorText ?? err.message}</p>`;
      }
    }
  }

  function clearAllFilters() {
    setActive();
    root.querySelectorAll('input[type="radio"]:checked').forEach((el) => (el.checked = false));
    root.querySelectorAll('input[type="checkbox"]:checked').forEach((el) => (el.checked = false));
    root.querySelectorAll('[data-filter-select]').forEach((el) => (el.selectedIndex = 0));
    const allPriceType = root.querySelector('input[type="radio"][name="price_type"][value="all"]');
    if (allPriceType) allPriceType.checked = true;
    const sliderEl = root.querySelector('[data-range-slider]');
    if (sliderEl?.noUiSlider) {
      sliderEl.noUiSlider.set([
        parseFloat(sliderEl.dataset.min),
        parseFloat(sliderEl.dataset.max),
      ]);
    }
    applyFilters(collectState());
  }

  const debouncedCheckbox = debounce(state, () => applyFilters(collectState()), DEBOUNCE_CHECKBOX);
  const debouncedPrice = debounce(state, () => applyFilters(collectState()), DEBOUNCE_PRICE);

  clearAllBtn?.addEventListener('click', clearAllFilters, { signal });

  root.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-remove-filter]');
    if (!btn) return;
    setActive();
    const key = btn.dataset.removeFilter;
    const val = btn.dataset.removeValue;

    const radio = root.querySelector(`input[type="radio"][name="${key}"][value="${val}"]`);
    if (radio) radio.checked = false;

    const checkbox = root.querySelector(`input[type="checkbox"][value="${val}"]`);
    if (checkbox) checkbox.checked = false;

    debouncedCheckbox();
  }, { signal });

  root.querySelectorAll('input[type="radio"], input[type="checkbox"]').forEach((el) => {
    el.addEventListener('change', () => {
      setActive();
      debouncedCheckbox();
    }, { signal });
  });

  root.querySelectorAll('[data-price-min], [data-price-max]').forEach((el) => {
    el.addEventListener('input', () => {
      setActive();
      debouncedPrice();
    }, { signal });
  });

  root.querySelectorAll('[data-filter-select]').forEach((el) =>
    el.addEventListener('change', () => {
      setActive();
      debouncedCheckbox();
    }, { signal })
  );

  root.querySelectorAll('[data-filter-search]').forEach((input) => {
    const listId = input.dataset.filterSearch;
    const list = root.querySelector(`[data-filter-list="${listId}"]`);
    if (!list) return;
    const items = [...list.querySelectorAll('li')];

    input.addEventListener(
      'input',
      debounce(state, () => {
        const q = input.value.toLowerCase().trim();
        items.forEach((li) => {
          li.hidden = q.length > 0 && !li.textContent.toLowerCase().includes(q);
        });
      }, 150),
      { signal }
    );
  });

  const sliderEl = root.querySelector('[data-range-slider]');
  if (sliderEl) {
    const min = parseFloat(sliderEl.dataset.min) || 0;
    const max = parseFloat(sliderEl.dataset.max) || 1000;
    const from = parseFloat(sliderEl.dataset.from) || min;
    const to = parseFloat(sliderEl.dataset.to) || max;

    const minInput = root.querySelector('[data-price-min]');
    const maxInput = root.querySelector('[data-price-max]');

    noUiSlider.create(sliderEl, {
      start: [from, to],
      connect: true,
      range: { min, max },
      step: 1,
      format: {
        to: (value) => Math.round(value),
        from: (value) => Number(value),
      },
    });
    state.noUiSliders.push(sliderEl.noUiSlider);

    sliderEl.noUiSlider.on('update', ([lo, hi]) => {
      if (minInput) minInput.value = lo;
      if (maxInput) maxInput.value = hi;
    });

    sliderEl.noUiSlider.on('change', () => {
      setActive();
      debouncedPrice();
    });

    if (minInput) {
      minInput.addEventListener('input', () => {
        setActive();
        sliderEl.noUiSlider.set([minInput.value, null]);
        debouncedPrice();
      }, { signal });
    }
    if (maxInput) {
      maxInput.addEventListener('input', () => {
        setActive();
        sliderEl.noUiSlider.set([null, maxInput.value]);
        debouncedPrice();
      }, { signal });
    }
  }

  function syncExpanded(isOpen) {
    openBtn.setAttribute('aria-expanded', String(isOpen));
  }

  function isDesktop() {
    return window.innerWidth >= LG_BREAKPOINT;
  }

  function moveTriggerToSlot() {
    if (triggerSlot && openBtn.parentElement !== triggerSlot) {
      triggerSlot.appendChild(openBtn);
    }
    instance.hidden = true;
    widgetShell?.classList.add('sobe-catalog-filters-widget--mobile-hidden');
    if (widgetShell) widgetShell.hidden = true;
  }

  function moveTriggerHome() {
    if (state.triggerHome.parentNode && openBtn.nextSibling !== state.triggerHome) {
      state.triggerHome.parentNode.insertBefore(openBtn, state.triggerHome);
    }
    instance.hidden = false;
    widgetShell?.classList.remove('sobe-catalog-filters-widget--mobile-hidden');
    if (widgetShell) widgetShell.hidden = false;
  }

  function moveToDrawer() {
    if (drawerBody && root.parentElement !== drawerBody) {
      drawerBody.appendChild(root);
    }
  }

  function moveToDesktop() {
    if (desktopContainer && root.parentElement !== desktopContainer) {
      desktopContainer.appendChild(root);
    }
  }

  function closeDrawer({ restoreFocus = true } = {}) {
    drawer.hidden = true;
    syncExpanded(false);
    if (isDesktop()) {
      moveToDesktop();
    } else {
      moveToDrawer();
    }

    if (restoreFocus) {
      const target = lastOpener?.isConnected ? lastOpener : openBtn;
      target?.focus?.({ preventScroll: true });
    }
  }

  function openDrawer(opener = openBtn) {
    if (activeController && activeController !== state) {
      activeController.closeDrawer({ restoreFocus: false });
    }

    setActive();
    lastOpener = opener;
    moveToDrawer();
    drawer.hidden = false;
    syncExpanded(true);
    const focusable = getFocusable(drawer);
    focusable[0]?.focus({ preventScroll: true });
  }

  function handleResize() {
    if (isDesktop()) {
      moveTriggerHome();
      closeDrawer({ restoreFocus: false });
    } else {
      if (triggerSlot) {
        moveTriggerToSlot();
      } else {
        moveTriggerHome();
      }
      moveToDrawer();
    }
  }

  state.resizeObserver = new ResizeObserver(handleResize);
  state.resizeObserver.observe(document.documentElement);
  handleResize();

  window.addEventListener('beforeunload', () => state.resizeObserver.disconnect(), { once: true, signal });

  openBtn.addEventListener('click', () => openDrawer(openBtn), { signal });

  closeButtons.forEach((button) => {
    button.addEventListener('click', () => closeDrawer(), { signal });
  });

  drawer.addEventListener('click', (e) => {
    const panelWidth = Math.min(
      parseInt(getComputedStyle(drawer).getPropertyValue('--filter-drawer-width') || '320', 10),
      window.innerWidth * 0.85
    );
    if (e.clientX > panelWidth) {
      closeDrawer();
    }
  }, { signal });

  drawer.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeDrawer();
      return;
    }
    if (e.key !== 'Tab') return;

    const focusable = getFocusable(drawer);
    if (focusable.length === 0) return;
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (e.shiftKey && document.activeElement === first) {
      e.preventDefault();
      last.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
      e.preventDefault();
      first.focus();
    }
  }, { signal });

  function hydrateFromUrl() {
    const urlParams = new URLSearchParams(location.search);

    urlParams.forEach((val, key) => {
      if (key === 'paged' || key === 's') return;

      if (key === 'orderby') {
        const orderSelect = document.querySelector('.woocommerce-ordering select[name="orderby"]');
        if (orderSelect) orderSelect.value = val;
        return;
      }

      if (key === 'min_price') {
        const el = root.querySelector('[data-price-min]');
        if (el) el.value = val;
        return;
      }

      if (key === 'max_price') {
        const el = root.querySelector('[data-price-max]');
        if (el) el.value = val;
        return;
      }

      if (key === 'price_type') {
        const radio = root.querySelector(`input[type="radio"][name="price_type"][value="${CSS.escape(val)}"]`);
        if (radio) radio.checked = true;
        return;
      }

      const slugs = splitFilterValue(val);
      const inputName = key.endsWith('[]') ? key : key + '[]';

      slugs.forEach((slug) => {
        const escaped = CSS.escape(slug);
        const cb = root.querySelector(`input[type="checkbox"][name="${inputName}"][value="${escaped}"]`);
        if (cb) { cb.checked = true; return; }
        const rb = root.querySelector(`input[type="radio"][name="${key}"][value="${escaped}"]`);
        if (rb) { rb.checked = true; return; }
        const sel = root.querySelector(`[data-filter-select="${CSS.escape(key)}"]`);
        if (sel) sel.value = slug;
      });
    });

    const activeSlider = root.querySelector('[data-range-slider]');
    if (activeSlider?.noUiSlider) {
      const minInput = root.querySelector('[data-price-min]');
      const maxInput = root.querySelector('[data-price-max]');
      activeSlider.noUiSlider.set([
        parseFloat(minInput?.value || activeSlider.dataset.min),
        parseFloat(maxInput?.value || activeSlider.dataset.max),
      ]);
    }
  }

  state.applyFilters = applyFilters;
  state.closeDrawer = closeDrawer;
  state.collectState = collectState;
  state.openDrawer = openDrawer;

  hydrateFromUrl();

  const initState = collectState();
  commitFilterStore(initState, params.action, params.nonce);
  updateClearAllVisibility(initState);

  if (!activeController) activeController = state;
  return state;
}

function init(root = document) {
  const resultCountEl = document.querySelector('.woocommerce-result-count');
  if (resultCountEl && !resultCountEl.hasAttribute('data-result-count')) {
    resultCountEl.setAttribute('data-result-count', '');
  }

  const initialized = [];

  getInstances(root).forEach((wrap) => {
    if (instances.has(wrap)) return;

    const params = readParams(wrap, 'catalog-filters', window.sobeCatalogParams);
    if (!params || !isCurrentContext(params, 'catalog-filters')) return;

    const state = initCatalogFilters(wrap, params);
    if (!state) return;

    instances.set(wrap, state);
    activeStates.add(state);
    initialized.push(state);
  });

  if (initialized.length === 0) return;

  if (activeStates.size > 1) {
    console.warn(
      '[sobe catalog-filters] Multiple filter instances found. Drawer controls are scoped per instance, but AJAX results still target the page-level WooCommerce product grid.'
    );
  }

  ensurePaginationListener();
}

function destroyState(state) {
  state.listenerController.abort();
  state.fetchController?.abort();
  state.resizeObserver?.disconnect();
  state.gridCtx?.revert();
  state.noUiSliders.forEach((slider) => slider.destroy());
  state.timers.forEach((timer) => clearTimeout(timer));
  state.timers.clear();

  restoreElement(state.root, state.original.rootParent, state.original.rootNext);
  restoreElement(state.openBtn, state.original.openBtnParent, state.original.openBtnNext);
  state.triggerHome.remove();
  restoreElement(state.drawer, state.original.drawerParent, state.original.drawerNext);

  state.instance.hidden = false;
  state.widgetShell?.classList.remove('sobe-catalog-filters-widget--mobile-hidden');
  if (state.widgetShell && state.original.widgetHidden !== null) {
    state.widgetShell.hidden = state.original.widgetHidden;
  }

  if (activeController === state) {
    activeController = null;
  }

  instances.delete(state.instance);
  activeStates.delete(state);
}

function destroy() {
  activeStates.forEach((state) => {
    destroyState(state);
  });

  paginationController?.abort();
  paginationController = null;
  activeController = null;
  resetFilterStore();
}

registerReinit('catalog-filters', { init, destroy });

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => init(document));
} else {
  init(document);
}

export { init, destroy };

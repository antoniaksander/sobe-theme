import noUiSlider from 'nouislider';
import { buildFilterUrl } from '../../js/filter-utils.js';
import { commit as commitFilterStore } from '../../js/filter-store.js';

(function () {
  const params = window.sobeCatalogParams;
  if (!params) return;

  const instances = [...document.querySelectorAll('[data-catalog-filters-instance]')];
  if (instances.length === 0) return;

  // Stamp the server-rendered WooCommerce result count so AJAX updates can target it.
  const _resultCountEl = document.querySelector('.woocommerce-result-count');
  if (_resultCountEl && !_resultCountEl.hasAttribute('data-result-count')) {
    _resultCountEl.setAttribute('data-result-count', '');
  }

  const archiveKey = params.archiveTaxonomy?.startsWith('pa_')
    ? 'filter_' + params.archiveTaxonomy.slice(3)
    : params.archiveTaxonomy;

  // Capture both the clean pathname and the initial query string once at load time.
  // buildFilterUrl always uses _pageBase so history.pushState drift never "escapes"
  // to /shop/ if wp_get_referer() fails and the server returns wrong pagination links.
  const _pageBase = location.origin + location.pathname;
  const _initSearch = new URLSearchParams(location.search);

  const DEBOUNCE_CHECKBOX = 300;
  const DEBOUNCE_PRICE = 500;
  const LG_BREAKPOINT = 768;

  let _activeFetch = null;
  let _fetchSeq = 0;
  let _gridCtx = null;
  let activeController = null;

  // ── Debounce ────────────────────────────────────────────────────────────────

  function debounce(fn, ms) {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), ms);
    };
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

  async function fetchFiltered(state) {
    if (_activeFetch) _activeFetch.abort();
    _activeFetch = new AbortController();

    const body = new FormData();
    body.append('action', params.action);
    body.append('nonce', params.nonce);
    body.append('filter_state', JSON.stringify(state));

    const res = await fetch(params.ajaxUrl, { method: 'POST', body, signal: _activeFetch.signal });
    if (!res.ok) throw new Error(res.status);
    return res.json();
  }

  function initCatalogFilters(instance) {
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

    const triggerHome = document.createComment('catalog filters trigger home');
    openBtn.after(triggerHome);

    // Body-mount the block-owned drawer so fixed positioning and z-index are not
    // trapped by archive/sidebar stacking contexts; JS keeps per-instance refs.
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
      activeController = controller;
    }

    // ── Filter state ──────────────────────────────────────────────────────────

    function collectState() {
      const state = {};

      root.querySelectorAll('input[type="radio"]:checked').forEach((el) => {
        state[el.name] = el.value;
      });

      root.querySelectorAll('input[type="checkbox"]:checked').forEach((el) => {
        const name = el.name.replace(/\[\]$/, '');
        if (!state[name]) state[name] = [];
        state[name].push(el.value);
      });

      const minInput = root.querySelector('[data-price-min]');
      const maxInput = root.querySelector('[data-price-max]');
      if (minInput) state.min_price = minInput.value;
      if (maxInput) state.max_price = maxInput.value;

      root.querySelectorAll('[data-filter-select]').forEach((el) => {
        if (el.value && el.value !== 'all') {
          state[el.dataset.filterSelect] = el.value;
        }
      });

      // Inject archive taxonomy context (e.g. /brand/nike/ → product_brand: "nike")
      if (params.archiveTaxonomy && params.archiveTerm) {
        if (!state[archiveKey] || (Array.isArray(state[archiveKey]) && state[archiveKey].length === 0)) {
          state[archiveKey] = params.archiveTerm;
        }
      }

      // Prefer the live WC ordering select; fall back to the initial page URL's orderby.
      const wcOrderSelect = document.querySelector('.woocommerce-ordering select[name="orderby"]');
      const orderby = wcOrderSelect?.value || _initSearch.get('orderby');
      if (orderby) state.orderby = orderby;

      // Preserve WC search query from the original page load (never changes mid-session).
      const searchQuery = _initSearch.get('s');
      if (searchQuery) state.s = searchQuery;

      return state;
    }

    function _buildFilterUrl(state) {
      const sliderEl = root.querySelector('[data-range-slider]');
      return buildFilterUrl(
        state,
        _pageBase,
        archiveKey,
        params.archiveTerm ?? null,
        {
          min: parseFloat(sliderEl?.dataset.min ?? 0),
          max: parseFloat(sliderEl?.dataset.max ?? Infinity),
        }
      );
    }

    // ── AJAX apply ────────────────────────────────────────────────────────────

    async function applyFilters(state) {
      setActive();
      const mySeq = ++_fetchSeq;

      const grid = document.querySelector('.products');
      const paginationZone = document.querySelector('[data-pagination]');
      const countEl = document.querySelector('[data-result-count]');

      try {
        const data = await fetchFiltered(state);

        // A newer request was dispatched while this one was in-flight — discard stale response.
        if (mySeq !== _fetchSeq) return;

        if (data.success === false) {
          throw new Error(data.data?.message ?? params.errorText);
        }

        if (grid && data.html !== undefined) {
          // Revert the previous gsap.context() — kills all animations (ScrollTriggers,
          // tweens, etc.) that were scoped to the old grid, preventing memory leaks.
          _gridCtx?.revert();

          grid.innerHTML = data.html;

          // Create a new context so all animations spawned by initAnimationBus are
          // trackable and can be reverted atomically on the next filter apply.
          _gridCtx = window.gsap?.context(() => {
            window.initAnimationBus?.();
          });
          window.ScrollTrigger?.refresh();
        }

        if (paginationZone && data.pagination_html !== undefined) {
          paginationZone.innerHTML = data.pagination_html;
          document.dispatchEvent(new CustomEvent('sobe:pagination-updated', {
            detail: { state, filterAction: params.action, filterNonce: params.nonce },
          }));
        }

        if (countEl && data.count_html !== undefined) {
          countEl.outerHTML = data.count_html;
        }

        commitFilterStore(state, params.action, params.nonce);
        syncChips(state);
        if (data.filters) updateFilterCounts(data);
        updateClearAllVisibility(state);
        history.pushState({}, '', _buildFilterUrl(state));

        // Scroll to listing — respect prefers-reduced-motion.
        const shopMain = document.querySelector('.shop-main');
        const target = shopMain ?? grid;
        if (target) {
          const y = target.getBoundingClientRect().top + window.scrollY;
          const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
          window.scrollTo({ top: Math.max(0, y - 24), behavior: reducedMotion ? 'instant' : 'smooth' });
        }

        if (drawer.hidden) {
          // Move focus to result count so screen readers announce the updated count.
          const newCountEl = document.querySelector('[data-result-count]');
          newCountEl?.focus({ preventScroll: true });
        } else if (!drawer.contains(document.activeElement)) {
          getFocusable(drawer)[0]?.focus({ preventScroll: true });
        }
      } catch (err) {
        if (err.name === 'AbortError') return;
        console.error('[sobe catalog-filters]', err);
        // Surface a user-facing message inside the pagination zone so it's visible
        // without scrolling (the grid may be blank if the request partially failed).
        if (paginationZone) {
          paginationZone.innerHTML =
            `<p class="sobe-filter-error" role="alert">${params.errorText ?? err.message}</p>`;
        }
      }
    }

    const debouncedCheckbox = debounce(() => applyFilters(collectState()), DEBOUNCE_CHECKBOX);
    const debouncedPrice = debounce(() => applyFilters(collectState()), DEBOUNCE_PRICE);

    // ── Clear all ──────────────────────────────────────────────────────────────

    function updateClearAllVisibility(state) {
      if (!clearAllBtn) return;
      const sliderEl = root.querySelector('[data-range-slider]');
      const hasActive = Object.keys(state).some((k) => {
        const v = state[k];
        if (k === archiveKey && v === params.archiveTerm) return false;
        if (k === 'paged' || k === 'orderby' || k === 's') return false;
        if (k === 'min_price') {
          const defaultMin = parseFloat(sliderEl?.dataset.min ?? 0);
          return parseFloat(v) > defaultMin;
        }
        if (k === 'max_price') {
          const defaultMax = parseFloat(sliderEl?.dataset.max ?? Infinity);
          return parseFloat(v) < defaultMax;
        }
        if (k === 'price_type') return v !== 'all';
        return Array.isArray(v) ? v.length > 0 : !!v;
      });
      clearAllBtn.hidden = !hasActive;
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

    clearAllBtn?.addEventListener('click', clearAllFilters);

    // ── Interdependent filter counts ───────────────────────────────────────────

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

    // ── Active filter chips ────────────────────────────────────────────────────

    function syncChips(state) {
      const zone = root.querySelector('[data-filter-chips]');
      if (!zone) return;
      zone.innerHTML = '';

      for (const [key, val] of Object.entries(state)) {
        if (key === archiveKey && val === params.archiveTerm) continue;
        if (key === 'min_price' || key === 'max_price') continue;
        if (key === 'price_type' && val === 'all') continue;
        if (key === 'paged' || key === 'orderby' || key === 's') continue;
        const values = Array.isArray(val) ? val : [val];
        values.forEach((v) => {
          if (!v) return;
          const btn = document.createElement('button');
          btn.className = 'sobe-filter-chip';
          btn.dataset.removeFilter = key;
          btn.dataset.removeValue = v;
          btn.setAttribute('aria-label', `${params.removeLabel} ${v}`);
          btn.innerHTML = `${v} <span class="sobe-filter-chip__remove" aria-hidden="true">${params.removeSymbol}</span>`;
          zone.appendChild(btn);
        });
      }
    }

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
    });

    // ── Input listeners ────────────────────────────────────────────────────────

    root.querySelectorAll('input[type="radio"], input[type="checkbox"]').forEach((el) => {
      el.addEventListener('change', () => {
        setActive();
        debouncedCheckbox();
      });
    });

    root.querySelectorAll('[data-price-min], [data-price-max]').forEach((el) => {
      el.addEventListener('input', () => {
        setActive();
        debouncedPrice();
      });
    });

    root.querySelectorAll('[data-filter-select]').forEach((el) =>
      el.addEventListener('change', () => {
        setActive();
        debouncedCheckbox();
      })
    );

    // ── Client-side filter search ─────────────────────────────────────────────

    root.querySelectorAll('[data-filter-search]').forEach((input) => {
      const listId = input.dataset.filterSearch;
      const list = root.querySelector(`[data-filter-list="${listId}"]`);
      if (!list) return;
      const items = [...list.querySelectorAll('li')];

      input.addEventListener(
        'input',
        debounce(() => {
          const q = input.value.toLowerCase().trim();
          items.forEach((li) => {
            li.hidden = q.length > 0 && !li.textContent.toLowerCase().includes(q);
          });
        }, 150)
      );
    });

    // ── noUiSlider price range ───────────────────────────────────────────────

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
          to: (v) => Math.round(v),
          from: (v) => Number(v),
        },
      });

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
        });
      }
      if (maxInput) {
        maxInput.addEventListener('input', () => {
          setActive();
          sliderEl.noUiSlider.set([null, maxInput.value]);
          debouncedPrice();
        });
      }
    }

    // ── Mobile drawer ──────────────────────────────────────────────────────────

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
      if (triggerHome.parentNode && openBtn.nextSibling !== triggerHome) {
        triggerHome.parentNode.insertBefore(openBtn, triggerHome);
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
      if (activeController && activeController !== controller) {
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

    const observer = new ResizeObserver(handleResize);
    observer.observe(document.documentElement);
    handleResize();
    window.addEventListener('beforeunload', () => observer.disconnect(), { once: true });

    openBtn.addEventListener('click', () => openDrawer(openBtn));

    closeButtons.forEach((button) => {
      button.addEventListener('click', () => closeDrawer());
    });

    drawer.addEventListener('click', (e) => {
      const panelWidth = Math.min(
        parseInt(getComputedStyle(drawer).getPropertyValue('--filter-drawer-width') || '320', 10),
        window.innerWidth * 0.85
      );
      if (e.clientX > panelWidth) {
        closeDrawer();
      }
    });

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
    });

    // ── URL state hydration ────────────────────────────────────────────────────
    // Pre-check / pre-select filter inputs to match the current URL query string so
    // that a direct load of e.g. ?filter_color=blue shows the correct active state.

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

        // Multi-value filter params are encoded as blue+red (URLSearchParams keeps raw '+').
        const slugs = val.split(' ').filter(Boolean); // URLSearchParams decodes '+' to space
        const inputName = key.endsWith('[]') ? key : key + '[]';

        slugs.forEach((slug) => {
          const escaped = CSS.escape(slug);
          // Try checkbox (filter_X[] format)
          const cb = root.querySelector(`input[type="checkbox"][name="${inputName}"][value="${escaped}"]`);
          if (cb) { cb.checked = true; return; }
          // Try radio (product_cat, product_tag — single-value)
          const rb = root.querySelector(`input[type="radio"][name="${key}"][value="${escaped}"]`);
          if (rb) { rb.checked = true; return; }
          // Try data-filter-select
          const sel = root.querySelector(`[data-filter-select="${CSS.escape(key)}"]`);
          if (sel) sel.value = slug;
        });
      });

      // Sync noUiSlider to hydrated price inputs
      const sliderEl = root.querySelector('[data-range-slider]');
      if (sliderEl?.noUiSlider) {
        const minInput = root.querySelector('[data-price-min]');
        const maxInput = root.querySelector('[data-price-max]');
        sliderEl.noUiSlider.set([
          parseFloat(minInput?.value || sliderEl.dataset.min),
          parseFloat(maxInput?.value || sliderEl.dataset.max),
        ]);
      }
    }

    hydrateFromUrl();

    const _initState = collectState();
    commitFilterStore(_initState, params.action, params.nonce);
    updateClearAllVisibility(_initState);

    const controller = {
      applyFilters,
      closeDrawer,
      collectState,
      openDrawer,
      root,
      drawer,
    };

    if (!activeController) activeController = controller;
    return controller;
  }

  const controllers = instances.map(initCatalogFilters).filter(Boolean);
  if (controllers.length === 0) return;

  if (controllers.length > 1) {
    console.warn(
      '[sobe catalog-filters] Multiple filter instances found. Drawer controls are scoped per instance, but AJAX results still target the page-level WooCommerce product grid.'
    );
  }

  // ── Pagination click intercept ───────────────────────────────────────────────
  // Prevent full-page navigation; preserve active filters when changing pages.

  document.addEventListener('click', (e) => {
    const link = e.target.closest('[data-pagination] a');
    if (!link) return;
    e.preventDefault();

    const href = new URL(link.href);

    let paged = href.searchParams.get('paged');

    if (!paged) {
      const match = href.pathname.match(/\/page\/(\d+)/);
      paged = match ? match[1] : '1';
    }

    const controller = activeController ?? controllers[0];
    controller.applyFilters({ ...controller.collectState(), paged: parseInt(paged, 10) });
  });
})();

import noUiSlider from 'nouislider';

(function () {
  const params = window.sobeCatalogParams;
  if (!params) return;

  const root = document.querySelector('[data-catalog-filters]');
  if (!root) return;

  const DEBOUNCE_CHECKBOX = 300;
  const DEBOUNCE_PRICE = 500;

  // ── Debounce ────────────────────────────────────────────────────────────────

  function debounce(fn, ms) {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), ms);
    };
  }

  // ── Filter state ────────────────────────────────────────────────────────────

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

    return state;
  }

  function buildFilterUrl(state) {
    const url = new URL(window.location.href);
    for (const [k] of url.searchParams.entries()) {
      url.searchParams.delete(k);
    }

    for (const [key, val] of Object.entries(state)) {
      if (key === 'paged') continue; // handled below
      if (key === 'price_type') {
        if (val && val !== 'all') url.searchParams.set('price_type', val);
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

  // ── AJAX fetch ──────────────────────────────────────────────────────────────

  async function fetchFiltered(state) {
    const body = new FormData();
    body.append('action', params.action);
    body.append('nonce', params.nonce);
    body.append('filter_state', JSON.stringify(state));

    const res = await fetch(params.ajaxUrl, { method: 'POST', body });
    return res.json();
  }

  async function applyFilters(state) {
    const grid = document.querySelector('.products');
    const paginationZone = document.querySelector('[data-pagination]');
    const countEl = document.querySelector('[data-result-count]');

    try {
      const data = await fetchFiltered(state);

      if (grid && data.html !== undefined) {
        grid.innerHTML = data.html;
      }

      if (paginationZone && data.pagination_html !== undefined) {
        paginationZone.innerHTML = data.pagination_html;
      }

      if (countEl && data.count !== undefined) {
        countEl.textContent = data.count;
      }

      syncChips(state);
      if (data.filters) updateFilterCounts(data);
      updateClearAllVisibility(state);
      history.pushState({}, '', buildFilterUrl(state));
    } catch (_err) {
      // silently fail — page state unchanged
    }
  }

  const debouncedCheckbox = debounce(() => applyFilters(collectState()), DEBOUNCE_CHECKBOX);
  const debouncedPrice = debounce(() => applyFilters(collectState()), DEBOUNCE_PRICE);

  // ── Clear all ────────────────────────────────────────────────────────────────

  const clearAllBtn = root.querySelector('[data-clear-all-filters]');

  function updateClearAllVisibility(state) {
    if (!clearAllBtn) return;
    const sliderEl = root.querySelector('[data-range-slider]');
    const hasActive = Object.keys(state).some((k) => {
      const v = state[k];
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
    applyFilters({});
  }

  clearAllBtn?.addEventListener('click', clearAllFilters);

  // ── Pagination click intercept ───────────────────────────────────────────────
  // Prevent full-page navigation; preserve active filters when changing pages.

  document.addEventListener('click', (e) => {
    const link = e.target.closest('[data-pagination] a');
    if (!link) return;
    e.preventDefault();
    const href = new URL(link.href);
    const paged = parseInt(href.searchParams.get('paged') || '1', 10);
    applyFilters({ ...collectState(), paged });
  });

  // ── Interdependent filter counts ─────────────────────────────────────────────

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

  // ── Active filter chips ──────────────────────────────────────────────────────

  function syncChips(state) {
    const zone = root.querySelector('[data-filter-chips]');
    if (!zone) return;
    zone.innerHTML = '';

    for (const [key, val] of Object.entries(state)) {
      if (key === 'min_price' || key === 'max_price') continue;
      if (key === 'price_type' && val === 'all') continue;
      const values = Array.isArray(val) ? val : [val];
      values.forEach((v) => {
        if (!v) return;
        const btn = document.createElement('button');
        btn.className = 'sobe-filter-chip';
        btn.dataset.removeFilter = key;
        btn.dataset.removeValue = v;
        btn.setAttribute('aria-label', `${params.removeLabel} ${v}`);
        btn.innerHTML = `${v} <span class="sobe-filter-chip__remove" aria-hidden="true">×</span>`;
        zone.appendChild(btn);
      });
    }
  }

  root.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-remove-filter]');
    if (!btn) return;
    const key = btn.dataset.removeFilter;
    const val = btn.dataset.removeValue;

    const radio = root.querySelector(`input[type="radio"][name="${key}"][value="${val}"]`);
    if (radio) radio.checked = false;

    const checkbox = root.querySelector(`input[type="checkbox"][value="${val}"]`);
    if (checkbox) checkbox.checked = false;

    debouncedCheckbox();
  });

  // ── Input listeners ──────────────────────────────────────────────────────────

  root.querySelectorAll('input[type="radio"], input[type="checkbox"]').forEach((el) => {
    el.addEventListener('change', debouncedCheckbox);
  });

  root.querySelectorAll('[data-price-min], [data-price-max]').forEach((el) => {
    el.addEventListener('input', debouncedPrice);
  });

  root.querySelectorAll('[data-filter-select]').forEach((el) =>
    el.addEventListener('change', debouncedCheckbox)
  );

  // ── Client-side filter search ─────────────────────────────────────────────

  root.querySelectorAll('[data-filter-search]').forEach((input) => {
    const listId = input.dataset.filterSearch;
    const list = root.querySelector(`[data-filter-list="${listId}"]`);
    if (!list) return;

    input.addEventListener('input', () => {
      const q = input.value.toLowerCase().trim();
      list.querySelectorAll('li').forEach((li) => {
        li.hidden = q.length > 0 && !li.textContent.toLowerCase().includes(q);
      });
    });
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

    sliderEl.noUiSlider.on('change', () => debouncedPrice());

    if (minInput) {
      minInput.addEventListener('input', () => {
        sliderEl.noUiSlider.set([minInput.value, null]);
        debouncedPrice();
      });
    }
    if (maxInput) {
      maxInput.addEventListener('input', () => {
        sliderEl.noUiSlider.set([null, maxInput.value]);
        debouncedPrice();
      });
    }
  }

  // ── Mobile drawer ────────────────────────────────────────────────────────────

  const drawer = document.getElementById('sobe-filter-drawer');
  const drawerBody = drawer?.querySelector('.sobe-filter-drawer__body');
  const openBtn = document.querySelector('[data-open-filter-drawer]');
  const sidebar = document.querySelector('.shop-sidebar');

  function moveToDrawer() {
    if (drawerBody && root.parentElement !== drawerBody) {
      drawerBody.appendChild(root);
    }
  }

  function moveToSidebar() {
    if (sidebar && root.parentElement !== sidebar) {
      sidebar.insertBefore(root, sidebar.firstChild);
      if (drawer) {
        drawer.hidden = true;
        openBtn?.setAttribute('aria-expanded', 'false');
      }
    }
  }

  const LG_BREAKPOINT = 1024;

  function handleResize() {
    if (window.innerWidth >= LG_BREAKPOINT) {
      moveToSidebar();
    } else {
      moveToDrawer();
    }
  }

  if (drawer && drawerBody && openBtn) {
    const observer = new ResizeObserver(handleResize);
    observer.observe(document.documentElement);
    handleResize();

    openBtn.addEventListener('click', () => {
      drawer.hidden = false;
      openBtn.setAttribute('aria-expanded', 'true');
      drawer.querySelector('button, input, [tabindex]')?.focus();
    });

    drawer.addEventListener('click', (e) => {
      const panelWidth = Math.min(320, window.innerWidth * 0.85);
      if (e.target.closest('[data-close-filter-drawer]') || e.clientX > panelWidth) {
        drawer.hidden = true;
        openBtn.setAttribute('aria-expanded', 'false');
        openBtn.focus();
      }
    });

    drawer.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        drawer.hidden = true;
        openBtn.setAttribute('aria-expanded', 'false');
        openBtn.focus();
      }
    });
  }

  // ── Init ─────────────────────────────────────────────────────────────────────

  updateClearAllVisibility(collectState());
})();

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

    return state;
  }

  function buildFilterUrl(state) {
    const url = new URL(window.location.href);
    const keep = ['paged'];
    for (const [k] of url.searchParams.entries()) {
      if (!keep.includes(k)) url.searchParams.delete(k);
    }

    for (const [key, val] of Object.entries(state)) {
      if (Array.isArray(val)) {
        url.searchParams.set('filter_' + key.replace(/^filter_/, ''), val.join('+'));
      } else if (val !== '' && val !== null && val !== undefined) {
        url.searchParams.set(key, val);
      }
    }
    url.searchParams.set('paged', '1');
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
      history.pushState({}, '', buildFilterUrl(state));
    } catch (_err) {
      // silently fail — page state unchanged
    }
  }

  const debouncedCheckbox = debounce(() => applyFilters(collectState()), DEBOUNCE_CHECKBOX);
  const debouncedPrice = debounce(() => applyFilters(collectState()), DEBOUNCE_PRICE);

  // ── Active filter chips ──────────────────────────────────────────────────────

  function syncChips(state) {
    const zone = root.querySelector('[data-filter-chips]');
    if (!zone) return;
    zone.innerHTML = '';

    for (const [key, val] of Object.entries(state)) {
      if (key === 'min_price' || key === 'max_price') continue;
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

  // ── Client-side filter search ─────────────────────────────────────────────

  root.querySelectorAll('[data-filter-search]').forEach((input) => {
    const listId = input.dataset.filterSearch;
    const list = root.querySelector(`[data-filter-list="${listId}"]`);
    if (!list) return;

    input.addEventListener('input', () => {
      const q = input.value.toLowerCase().trim();
      list.querySelectorAll('li').forEach((li) => {
        const text = li.textContent.toLowerCase();
        li.hidden = q.length > 0 && !text.includes(q);
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
  const closeBtn = drawer?.querySelector('[data-close-filter-drawer]');
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

    closeBtn?.addEventListener('click', () => {
      drawer.hidden = true;
      openBtn.setAttribute('aria-expanded', 'false');
      openBtn.focus();
    });

    drawer.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        drawer.hidden = true;
        openBtn.setAttribute('aria-expanded', 'false');
        openBtn.focus();
      }
    });
  }
})();

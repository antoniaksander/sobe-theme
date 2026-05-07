(function () {
  const params = window.sobeLoadMoreParams;
  if (!params) return;

  const sentinel = document.querySelector('[data-load-more-sentinel]');
  if (!sentinel) return;

  const btn = sentinel.querySelector('button');
  let loading = false;

  const observer = new IntersectionObserver(
    (entries) => {
      if (entries[0].isIntersecting && !loading) {
        loadMore();
      }
    },
    { rootMargin: '200px' },
  );

  observer.observe(sentinel);

  async function loadMore() {
    loading = true;
    if (btn) btn.textContent = params.loadingText;

    const page = parseInt(sentinel.dataset.page, 10);
    const body = new FormData();
    body.append('action', params.ajaxAction);
    body.append('nonce', params.nonce);
    body.append('page', page);
    body.append('taxonomy', params.taxonomy ?? '');
    body.append('term_id', params.termId ?? 0);
    body.append('search', params.search ?? '');
    body.append('orderby', params.orderby ?? 'menu_order');

    try {
      const res = await fetch(params.ajaxUrl, { method: 'POST', body });
      const data = await res.json();

      if (data.html) {
        const grid = document.querySelector('.woocommerce ul.products');
        if (grid) grid.insertAdjacentHTML('beforeend', data.html);
      }

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

      if (btn) btn.textContent = params.loadedText;
    } catch {
      loading = false;
    }
  }
})();

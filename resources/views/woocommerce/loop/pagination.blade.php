@php
  $pfx     = config('theme.prefix');
  $mode    = get_theme_mod("{$pfx}_shop_pagination_mode", 'paginated');
  global $wp_query;
  $total   = (int) $wp_query->max_num_pages;
  // Use $wp_query->get() so it reads the overridden query in AJAX context,
  // unlike get_query_var() which always reads $wp_the_query (the original request).
  $current = max(1, (int) ($wp_query->get('paged') ?: 1));

  // Build a reliable base URL that works in both AJAX and normal page context.
  // get_previous/next_posts_page_link() generate admin-ajax.php URLs in AJAX context.
  if (wp_doing_ajax()) {
      $referer = wp_get_referer();
      $base = $referer
          ? remove_query_arg('paged', $referer)
          : get_permalink(wc_get_page_id('shop'));
  } elseif (is_product_taxonomy()) {
      $obj  = get_queried_object();
      $base = ($obj instanceof \WP_Term) ? get_term_link($obj) : get_permalink(wc_get_page_id('shop'));
  } else {
      $base = get_permalink(wc_get_page_id('shop'));
  }
  $prevUrl = ($current > 1)      ? add_query_arg('paged', $current - 1, $base) : null;
  $nextUrl = ($current < $total) ? add_query_arg('paged', $current + 1, $base) : null;
@endphp

@if ($total > 1)
  @if ($mode === 'paginated')
    <nav class="sobe-pagination" aria-label="{{ __('Products pagination', 'sobe') }}">
      @if ($prevUrl)
        <a class="sobe-pagination__arrow" href="{{ esc_url($prevUrl) }}" rel="prev"
           aria-label="{{ __('Previous page', 'sobe') }}">←</a>
      @else
        <span class="sobe-pagination__arrow sobe-pagination__arrow--disabled"
              aria-disabled="true" aria-label="{{ __('Previous page', 'sobe') }}">←</span>
      @endif

      <span class="sobe-pagination__current">
        {{ sprintf(__('Page %1$d of %2$d', 'sobe'), $current, $total) }}
      </span>

      @if ($nextUrl)
        <a class="sobe-pagination__arrow" href="{{ esc_url($nextUrl) }}" rel="next"
           aria-label="{{ __('Next page', 'sobe') }}">→</a>
      @else
        <span class="sobe-pagination__arrow sobe-pagination__arrow--disabled"
              aria-disabled="true" aria-label="{{ __('Next page', 'sobe') }}">→</span>
      @endif
    </nav>
  @elseif ($mode === 'load-more' && $current < $total)
    <div class="sobe-load-more-sentinel"
         data-load-more-sentinel
         data-page="{{ $current + 1 }}"
         data-total="{{ $total }}"
         aria-hidden="true">
      <button class="sr-only" aria-live="polite">{{ __('Load more products', 'sobe') }}</button>
    </div>
  @endif
@endif

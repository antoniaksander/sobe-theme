@php
  $pfx     = config('theme.prefix');
  $mode    = get_theme_mod("{$pfx}_shop_pagination_mode", 'paginated');
  global $wp_query;
  $total   = (int) $wp_query->max_num_pages;
  $current = max(1, (int) get_query_var('paged'));
@endphp

@if ($total > 1)
  @if ($mode === 'paginated')
    <nav class="sobe-pagination" aria-label="{{ __('Products pagination', 'sobe') }}">
      @php $prevUrl = get_previous_posts_page_link(); @endphp
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

      @php $nextUrl = get_next_posts_page_link($total); @endphp
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

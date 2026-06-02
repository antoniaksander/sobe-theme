@extends('layouts.app')

@section('content')
  @php
    global $wp_query;

    $searchQuery  = get_search_query();
    $isWooCommerce = class_exists('WooCommerce');
    $totalPages   = max(1, (int) $wp_query->max_num_pages);
    $currentPage  = max(1, (int) get_query_var('paged'));
    $prevUrl      = $currentPage > 1 ? get_pagenum_link($currentPage - 1) : null;
    $nextUrl      = $currentPage < $totalPages ? get_pagenum_link($currentPage + 1) : null;
  @endphp

  @if (! have_posts())
    <section class="py-16">
      <div class="max-w-standard mx-auto px-6 lg:px-8 text-center">

        {{-- No Results Icon --}}
        <div class="mb-6">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="size-16 mx-auto text-text-subtle" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
          </svg>
        </div>

        {{-- No Results Message --}}
        <h2 class="text-2xl font-semibold text-heading mb-2">
          {!! __('No results found', 'sobe') !!}
        </h2>
        <p class="text-text-muted mb-8 max-w-reading mx-auto">
          {!! __('We couldn\'t find any results for your search. Try different keywords or browse our content.', 'sobe') !!}
        </p>

        {{-- Search Form --}}
        <div class="max-w-md mx-auto">
          {!! get_search_form(false) !!}
        </div>

        {{-- Suggestions --}}
        <div class="mt-12">
          <h3 class="text-sm font-semibold uppercase tracking-wider text-text-subtle mb-4">
            {!! __('Popular Pages', 'sobe') !!}
          </h3>
          <nav class="flex flex-wrap justify-center gap-3">
            <a
              href="{{ home_url('/') }}"
              class="px-4 py-2 text-sm rounded-lg bg-surface-2 text-text hover:bg-surface-3 transition-colors duration-200"
            >
              {!! __('Homepage', 'sobe') !!}
            </a>
            <a
              href="{{ get_permalink(get_option('page_for_posts')) }}"
              class="px-4 py-2 text-sm rounded-lg bg-surface-2 text-text hover:bg-surface-3 transition-colors duration-200"
            >
              {!! __('Blog', 'sobe') !!}
            </a>
          </nav>
        </div>

      </div>
    </section>
  @else
    {{-- Results Loop --}}
    <section class="py-12">
      <div class="max-w-standard mx-auto px-6 lg:px-8">

        {{-- Header: title + result count --}}
        <header class="mb-8">
          <h1 class="text-2xl font-semibold text-heading mb-1">
            @if ($searchQuery)
              {!! sprintf(__('Results for "%s"', 'sobe'), esc_html($searchQuery)) !!}
            @else
              {!! __('Search results', 'sobe') !!}
            @endif
          </h1>
          <p class="text-sm text-text-muted">
            {!! sprintf(__('Showing %1$d results for "%2$s"', 'sobe'),
              $wp_query->found_posts, esc_html($searchQuery)) !!}
          </p>
        </header>

        {{-- Results grid — wrapped in .woocommerce so product cards pick up WC styles --}}
        <div class="{{ $isWooCommerce ? 'woocommerce' : '' }}">
          <ul class="products search-results-grid">
            @while(have_posts()) @php the_post(); @endphp
              @if(get_post_type() === 'product')
                @include('partials.search-result-product')
              @elseif(get_post_type() === 'post')
                @include('partials.search-result-post')
              @else
                @include('partials.search-result-page')
              @endif
            @endwhile
          </ul>
        </div>

        {{-- Pagination --}}
        @if ($totalPages > 1)
          <nav class="sobe-pagination mt-12" aria-label="{{ __('Search pagination', 'sobe') }}">
            @if ($prevUrl)
              <a class="sobe-pagination__arrow" href="{!! esc_url($prevUrl) !!}" rel="prev" aria-label="{{ __('Previous page', 'sobe') }}">←</a>
            @else
              <span class="sobe-pagination__arrow sobe-pagination__arrow--disabled" aria-disabled="true" aria-label="{{ __('Previous page', 'sobe') }}">←</span>
            @endif

            <span class="sobe-pagination__current">
              {{ sprintf(__('Page %1$d of %2$d', 'sobe'), $currentPage, $totalPages) }}
            </span>

            @if ($nextUrl)
              <a class="sobe-pagination__arrow" href="{!! esc_url($nextUrl) !!}" rel="next" aria-label="{{ __('Next page', 'sobe') }}">→</a>
            @else
              <span class="sobe-pagination__arrow sobe-pagination__arrow--disabled" aria-disabled="true" aria-label="{{ __('Next page', 'sobe') }}">→</span>
            @endif
          </nav>
        @endif

      </div>
    </section>
  @endif
@endsection

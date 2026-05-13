@extends('layouts.app')

@section('content')
  @include('partials.page-header')

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

        {{-- Header row: count + re-search form --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
          <p class="text-sm text-text-muted">
            {!! sprintf(__('Showing %1$d results for "%2$s"', 'sobe'),
              $wp_query->found_posts, get_search_query()) !!}
          </p>
          <div class="max-w-xs w-full">
            {!! get_search_form(false) !!}
          </div>
        </div>

        {{-- Results grid --}}
        <div class="search-results-grid">
          @while(have_posts()) @php the_post(); @endphp
            @if(get_post_type() === 'product')
              @include('partials.search-result-product')
            @elseif(get_post_type() === 'post')
              @include('partials.search-result-post')
            @else
              @include('partials.search-result-page')
            @endif
          @endwhile
        </div>

        {{-- Pagination --}}
        <div class="mt-12">
          {!! get_the_posts_pagination([
            'mid_size'  => 2,
            'prev_text' => '←',
            'next_text' => '→',
            'class'     => 'sobe-wp-pagination',
          ]) !!}
        </div>

      </div>
    </section>
  @endif
@endsection
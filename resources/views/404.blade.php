@extends('layouts.app')

@section('content')
  <section class="py-24 md:py-32">
    <div class="max-w-standard mx-auto px-6 lg:px-8 text-center">

      {{-- 404 Visual --}}
      <div class="mb-8">
        <span class="text-8xl font-bold text-accent">404</span>
      </div>

      {{-- Heading --}}
      <h1 class="text-4xl md:text-5xl font-bold text-heading mb-4">
        {!! __('Page Not Found', 'sobe') !!}
      </h1>

      {{-- Message --}}
      <p class="text-lg text-text-muted max-w-reading mx-auto mb-8">
        {!! __('Oops! The page you\'re looking for doesn\'t exist. It may have been moved or deleted.', 'sobe') !!}
      </p>

      {{-- Search Form --}}
      <div class="max-w-md mx-auto mb-12">
        {!! get_search_form(false) !!}
      </div>

      {{-- Popular Links --}}
      <div class="border-t border-border pt-12">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-text-subtle mb-6">
          {!! __('Popular Pages', 'sobe') !!}
        </h2>
        <nav class="flex flex-wrap justify-center gap-4">
          <a
            href="{{ home_url('/') }}"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-surface-2 text-text hover:bg-surface-3 transition-colors duration-200"
          >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
              <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v9a2.25 2.25 0 0 0 2.25 2.25h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25H9a2.25 2.25 0 0 0-2.25 2.25v.75m-6-4.5h6m-6 0V6a2.25 2.25 0 0 1 2.25-2.25h3a2.25 2.25 0 0 1 2.25 2.25v.75m-9 0h9" />
            </svg>
            {!! __('Homepage', 'sobe') !!}
          </a>
          <a
            href="{{ get_permalink(get_option('page_for_posts')) }}"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-surface-2 text-text hover:bg-surface-3 transition-colors duration-200"
          >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.104.875 5.472 2.286M12 6.042V4.5m0 1.542A8.974 8.974 0 0 1 18 3.75c2.305 0 4.104.875 5.472 2.286" />
            </svg>
            {!! __('Blog', 'sobe') !!}
          </a>
          @if (class_exists('WooCommerce') && function_exists('wc_get_page_id'))
            <a
              href="{{ get_permalink(wc_get_page_id('shop')) }}"
              class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-surface-2 text-text hover:bg-surface-3 transition-colors duration-200"
            >
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z" />
              </svg>
              {!! __('Shop', 'sobe') !!}
            </a>
          @endif
        </nav>
      </div>

    </div>
  </section>
@endsection
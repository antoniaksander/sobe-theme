@extends('layouts.app')

@section('content')
  @while(have_posts()) @php
    the_post();

    $rawContent = get_post_field('post_content', get_the_ID()) ?: '';
    $plainContent = trim(wp_strip_all_tags(strip_shortcodes($rawContent)));
    $hasMeaningfulContent = is_page() && ($plainContent !== '' || has_blocks($rawContent));
    $shopPageId = function_exists('wc_get_page_id') ? (int) wc_get_page_id('shop') : 0;
    $shopUrl = $shopPageId > 0 ? get_permalink($shopPageId) : null;
    $blogPageId = (int) get_option('page_for_posts');
    $blogUrl = $blogPageId > 0 ? get_permalink($blogPageId) : null;
  @endphp

    @if ($hasMeaningfulContent)
      <div class="front-page-blocks is-layout-constrained max-w-none">
        @php the_content(); @endphp
      </div>
    @else
      <x-section padding="hero" width="standard" class="bg-background">
        <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-center">
          <div class="max-w-3xl">
            <p class="mb-4 text-sm font-semibold uppercase tracking-wider text-text-muted">
              {{ __('WordPress commerce platform', 'sobe') }}
            </p>
            <h1 class="font-heading text-4xl md:text-6xl font-bold leading-tight text-heading">
              {{ get_bloginfo('name') }}
            </h1>
            <p class="mt-6 text-lg md:text-xl leading-relaxed text-text-muted">
              {{ get_bloginfo('description') ?: __('A flexible foundation for modern product, content, and service experiences.', 'sobe') }}
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
              @if ($shopUrl)
                <a class="btn btn-primary" href="{{ esc_url($shopUrl) }}">{{ __('Browse shop', 'sobe') }}</a>
              @endif
              @if ($blogUrl)
                <a class="btn btn-outline" href="{{ esc_url($blogUrl) }}">{{ __('Read updates', 'sobe') }}</a>
              @else
                <a class="btn btn-outline" href="{{ esc_url(home_url('/?s=')) }}">{{ __('Search site', 'sobe') }}</a>
              @endif
            </div>
          </div>

          <div class="rounded-lg border border-border bg-surface-1 p-6 shadow-sm">
            <h2 class="font-heading text-xl font-semibold text-heading">{{ __('Ready to customize', 'sobe') }}</h2>
            <ul class="mt-5 space-y-4 text-sm text-text-muted">
              <li>{{ __('Header and footer render immediately on activation.', 'sobe') }}</li>
              <li>{{ __('Navigation falls back to site pages until menus are assigned.', 'sobe') }}</li>
              <li>{{ __('Design tokens, blocks, and WooCommerce surfaces are ready for client branding.', 'sobe') }}</li>
            </ul>
          </div>
        </div>
      </x-section>

      <x-section padding="default" width="standard" class="bg-surface-1">
        <div class="grid gap-6 md:grid-cols-3">
          @foreach ([
            ['title' => __('Reusable blocks', 'sobe'), 'text' => __('Start with platform blocks, then add client-specific blocks alongside them.', 'sobe')],
            ['title' => __('Commerce ready', 'sobe'), 'text' => __('Catalog, product detail, side-cart, search, and filter surfaces are wired for WooCommerce.', 'sobe')],
            ['title' => __('Client-owned styling', 'sobe'), 'text' => __('Brand tokens, fonts, logos, and content can be replaced without breaking upstream contracts.', 'sobe')],
          ] as $item)
            <article class="rounded-lg border border-border bg-background p-6">
              <h2 class="font-heading text-xl font-semibold text-heading">{{ $item['title'] }}</h2>
              <p class="mt-3 text-sm leading-relaxed text-text-muted">{{ $item['text'] }}</p>
            </article>
          @endforeach
        </div>
      </x-section>
    @endif
  @endwhile
@endsection

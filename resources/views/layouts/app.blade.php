<!doctype html>
<html @php language_attributes(); @endphp x-data="app" :class="{ dark: dark }" @open-cart.window="openCart($event)">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
      // Baseline SEO meta — skipped automatically when a dedicated SEO plugin is active.
      // Supported: Yoast SEO, Rank Math, All in One SEO, SEOPress.
      $sobe_seo_active = defined('WPSEO_VERSION')
                      || defined('RANK_MATH_VERSION')
                      || defined('AIOSEO_VERSION')
                      || function_exists('seopress_init')
                      || apply_filters('sobe/seo/disable_baseline', false)
                      || apply_filters('sobe_disable_baseline_seo', false);
      if (! $sobe_seo_active) {
        $sobe_title       = wp_get_document_title();
        $sobe_desc        = is_singular() && has_excerpt()
                              ? wp_strip_all_tags(get_the_excerpt())
                              : get_bloginfo('description');
        $sobe_url         = is_singular()
                              ? get_permalink()
                              : (is_home() || is_front_page() ? home_url('/') : home_url(add_query_arg([])));
        $sobe_type        = is_single() ? 'article' : 'website';
        $sobe_site_name   = get_bloginfo('name');
        $sobe_image       = is_singular() && has_post_thumbnail()
                              ? get_the_post_thumbnail_url(null, 'large')
                              : get_site_icon_url(512);
      }
    @endphp
    @if (! $sobe_seo_active)
      <link rel="canonical" href="{{ esc_url($sobe_url) }}">
      <meta name="description" content="{{ esc_attr($sobe_desc) }}">
      <meta property="og:title" content="{{ esc_attr($sobe_title) }}">
      <meta property="og:description" content="{{ esc_attr($sobe_desc) }}">
      <meta property="og:url" content="{{ esc_url($sobe_url) }}">
      <meta property="og:type" content="{{ $sobe_type }}">
      <meta property="og:site_name" content="{{ esc_attr($sobe_site_name) }}">
      @if ($sobe_image)
        <meta property="og:image" content="{{ esc_url($sobe_image) }}">
        <meta name="twitter:image" content="{{ esc_url($sobe_image) }}">
      @endif
      @if ($sobe_type === 'article')
        <meta property="article:published_time" content="{{ get_the_date('c') }}">
        <meta property="article:modified_time" content="{{ get_the_modified_date('c') }}">
        @if (get_the_author())
          <meta property="article:author" content="{{ esc_attr(get_the_author()) }}">
        @endif
      @endif
      <meta name="twitter:card" content="summary_large_image">
      <meta name="twitter:title" content="{{ esc_attr($sobe_title) }}">
      <meta name="twitter:description" content="{{ esc_attr($sobe_desc) }}">
      @if (is_front_page())
        @php
          $sobe_schema = ['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => $sobe_site_name, 'url' => home_url('/')];
          if ($sobe_image) $sobe_schema['logo'] = $sobe_image;
        @endphp
        <script type="application/ld+json">{!! wp_json_encode($sobe_schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
      @endif
    @endif
    @php wp_head(); @endphp
    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body @php body_class(); @endphp>
    @php wp_body_open(); @endphp

    <div class="sr-only" aria-live="polite" aria-atomic="true" x-text="cartAnnouncement"></div>

    <a class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:rounded focus:px-4 focus:py-2 focus:bg-surface-1 focus:text-text focus:outline-none" href="#main">
      {{ __('Skip to content', 'sobe') }}
    </a>

    @php do_action('get_header'); @endphp

    @include('partials.announcement-bar')

    @if (function_exists('is_checkout') && is_checkout())
      @include('sections.checkout-header')
    @else
      {!! \App\sobe_render_layout_pattern('header', get_theme_mod(config('theme.prefix') . '_header_layout', 'header-1')) !!}
    @endif

    <main id="main" class="relative z-10">
      @yield('content')
    </main>

    @if (! (function_exists('is_checkout') && is_checkout()))
      @include('sections.footer')
    @endif

    <x-side-cart />

    <div class="fixed bottom-6 right-6 z-[9999] flex flex-col gap-3 pointer-events-none" aria-live="polite">
      <x-toast-container />
    </div>

    @include(apply_filters('sobe/search/overlay_view', 'partials.search-overlay'))

    @php do_action('get_footer'); @endphp
    @php wp_footer(); @endphp
  </body>
</html>

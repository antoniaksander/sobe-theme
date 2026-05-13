<!doctype html>
<html @php(language_attributes()) x-data="app" :class="{ dark: dark }" @open-cart.window="openCart($event)">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php(wp_head())
    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body @php(body_class())>
    @php(wp_body_open())

    <canvas id="global-webgl" class="fixed inset-0 pointer-events-none z-0" aria-hidden="true"></canvas>
    <div class="sr-only" aria-live="polite" aria-atomic="true" x-text="cartAnnouncement"></div>

    <a class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:rounded focus:px-4 focus:py-2 focus:bg-surface-1 focus:text-text focus:outline-none" href="#main">
      {{ __('Skip to content', 'sobe') }}
    </a>

    @php(do_action('get_header'))

    <main id="main" class="relative z-10 pt-16">
      @yield('content')
    </main>

    @php(do_action('get_footer'))
    @php(wp_footer())
  </body>
</html>

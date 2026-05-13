{{-- Header 2 — Centered: Menu Left | Logo Center | Cart Right --}}
<header class="site-header">
  <div class="max-w-standard mx-auto px-lg lg:px-xl h-16 flex items-center justify-between relative">

    {{-- Left slot: hamburger on mobile, nav on desktop --}}
    <div class="flex items-center shrink-0 z-10">
      <button
        class="md:hidden flex items-center justify-center w-10 h-10 rounded-lg text-text hover:bg-surface-2 transition-colors duration-200"
        @click="navOpen = !navOpen"
        :aria-expanded="navOpen.toString()"
        :aria-label="navOpen ? '{{ __('Close menu', 'sobe') }}' : '{{ __('Open menu', 'sobe') }}'"
      >
        <svg x-show="!navOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
        </svg>
        <svg x-show="navOpen" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
      </button>
      @if (has_nav_menu('primary_navigation'))
        <nav
          class="hidden md:flex"
          aria-label="{{ wp_get_nav_menu_name('primary_navigation') }}"
        >
          {!! wp_nav_menu([
            'theme_location' => 'primary_navigation',
            'menu_class'     => 'flex items-center gap-8 text-sm font-medium list-none text-text',
            'container'      => false,
            'echo'           => false,
          ]) !!}
        </nav>
      @endif
    </div>

    {{-- Logo — absolutely centred --}}
    <a
      class="absolute left-1/2 -translate-x-1/2 font-semibold text-lg text-heading"
      href="{{ home_url('/') }}"
    >
      @if ($logo)
        <img
          class="!h-8 w-auto max-w-[160px] object-contain"
          src="{{ $logo }}"
          alt="{{ $siteName }}"
          x-show="!dark"
        />
      @endif
      @if ($darkLogo)
        <img
          class="!h-8 w-auto max-w-[160px] object-contain"
          src="{{ $darkLogo }}"
          alt="{{ $siteName }}"
          x-show="dark"
          x-cloak
        />
      @endif
      @if (!$logo && !$darkLogo)
        {{ $siteName }}
      @endif
    </a>

    {{-- Right actions --}}
    <div class="flex items-center gap-2 shrink-0 z-10">

      @if (get_theme_mod(config('theme.prefix') . '_enable_dark_toggle', false))
        <x-dark-mode-toggle />
      @endif

      <button
        @click="$dispatch('open-search')"
        class="flex items-center justify-center w-10 h-10 rounded-lg text-text hover:bg-surface-2 transition-colors duration-200"
        aria-label="{{ __('Search products', 'sobe') }}"
      >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>
      </button>

      @include('components.wishlist-icon')

      @if (class_exists('WooCommerce') && get_theme_mod(config('theme.prefix') . '_enable_side_cart', true))
        <div
          class="relative"
          x-data="{ cartCount: {{ WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0 }} }"
          @cart-updated.window="cartCount = $event.detail.count"
        >
          <button
            @click="$dispatch('open-cart', { trigger: $event.currentTarget })"
            class="flex items-center justify-center w-10 h-10 rounded-lg text-text hover:bg-surface-2 transition-colors duration-200"
            aria-label="{{ __('Open cart', 'sobe') }}"
          >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z" />
            </svg>
            <span
              class="sobe-cart-count absolute -top-1 -right-1 size-4 flex items-center justify-center rounded-full bg-accent text-accent-fg text-[10px] font-bold leading-none"
              :class="cartCount > 0 ? '' : 'hidden'"
              x-text="cartCount"
              aria-live="polite"
              aria-label="{{ __('Cart item count', 'sobe') }}"
            ></span>
          </button>
        </div>
      @endif

    </div>
  </div>
</header>

{{-- Mobile dropdown --}}
<div
  x-show="navOpen"
  x-cloak
  x-trap.noscroll="navOpen"
  x-transition:enter="transition ease-out duration-200"
  x-transition:enter-start="opacity-0 -translate-y-1"
  x-transition:enter-end="opacity-100 translate-y-0"
  x-transition:leave="transition ease-in duration-150"
  x-transition:leave-start="opacity-100 translate-y-0"
  x-transition:leave-end="opacity-0 -translate-y-1"
  class="fixed inset-x-0 top-16 z-40 bg-surface-1 border-b border-border md:hidden shadow-sm"
  @keydown.escape.window="navOpen = false"
>
  @if (has_nav_menu('primary_navigation'))
    <nav class="max-w-standard mx-auto px-lg py-md" aria-label="{{ __('Mobile navigation', 'sobe') }}" data-nav-mobile>
      {!! wp_nav_menu([
        'theme_location' => 'primary_navigation',
        'menu_class'     => 'flex flex-col gap-md text-base font-medium list-none text-text',
        'container'      => false,
        'echo'           => false,
      ]) !!}
    </nav>
  @endif
</div>

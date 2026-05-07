{{-- Header 3 — Minimal: Hamburger Left | Logo Center | Cart Right --}}
<header class="site-header">
  <div class="max-w-standard mx-auto px-lg lg:px-xl h-16 flex items-center justify-between relative">

    {{-- Hamburger (all viewports) --}}
    <button
      class="flex items-center justify-center w-10 h-10 rounded-lg text-text hover:bg-surface-2 transition-colors duration-200 shrink-0 z-10"
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

{{-- Full-height slide-out nav from left --}}
<div
  x-show="navOpen"
  x-cloak
  class="fixed inset-0 z-40 flex"
  @keydown.escape.window="navOpen = false"
  x-transition:enter="transition ease-out duration-300"
  x-transition:enter-start="opacity-0"
  x-transition:enter-end="opacity-100"
  x-transition:leave="transition ease-in duration-200"
  x-transition:leave-start="opacity-100"
  x-transition:leave-end="opacity-0"
>
  {{-- Backdrop --}}
  <div
    class="absolute inset-0 bg-black/40"
    @click="navOpen = false"
    aria-hidden="true"
  ></div>

  {{-- Slide-in panel --}}
  <div
    x-trap.noscroll="navOpen"
    role="dialog"
    aria-modal="true"
    aria-label="{{ __('Navigation', 'sobe') }}"
    class="relative w-80 max-w-[85vw] bg-surface-1 h-full shadow-2xl flex flex-col overflow-y-auto"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="-translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="-translate-x-full"
  >
    {{-- Panel header --}}
    <div class="flex items-center justify-between px-lg h-16 border-b border-border shrink-0">
      <a class="font-semibold text-lg text-heading" href="{{ home_url('/') }}">
        @if ($logo)
          <img
            class="!h-7 w-auto max-w-[140px] object-contain"
            src="{{ $logo }}"
            alt="{{ $siteName }}"
            x-show="!dark"
          />
        @endif
        @if ($darkLogo)
          <img
            class="!h-7 w-auto max-w-[140px] object-contain"
            src="{{ $darkLogo }}"
            alt="{{ $siteName }}"
            x-show="dark"
            x-cloak
          />
        @endif
        @if (!$logo && !$darkLogo)
          {!! $siteName !!}
        @endif
      </a>
      <button
        @click="navOpen = false"
        class="flex items-center justify-center w-8 h-8 rounded-lg text-text-muted hover:bg-surface-2 transition-colors duration-200"
        aria-label="{{ __('Close menu', 'sobe') }}"
      >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    {{-- Nav links --}}
    @if (has_nav_menu('primary_navigation'))
      <nav class="flex-1 px-lg py-xl" aria-label="{{ __('Primary navigation', 'sobe') }}" data-nav-panel>
        {!! wp_nav_menu([
          'theme_location' => 'primary_navigation',
          'menu_class'     => 'flex flex-col gap-1 list-none text-text',
          'container'      => false,
          'echo'           => false,
          'depth'          => 2,
          'walker'         => null,
        ]) !!}
      </nav>
    @endif
  </div>
</div>

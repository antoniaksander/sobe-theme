<header class="border-b border-border bg-surface-1">
  <div class="container mx-auto px-4 h-16 grid grid-cols-3 items-center">

    <a
      href="{{ wc_get_cart_url() }}"
      class="flex items-center gap-1.5 text-sm text-text-muted hover:text-text transition-colors duration-200 w-fit"
      aria-label="{{ __('Return to store', 'sobe') }}"
    >
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
      </svg>
      {{ __('Return to store', 'sobe') }}
    </a>

    <a class="justify-self-center font-semibold text-lg text-heading" href="{{ home_url('/') }}">
      @if ($logo)
        <img
          class="!h-8 w-auto max-w-[140px] object-contain"
          src="{{ $logo }}"
          alt="{{ $siteName }}"
          x-show="!dark"
        />
      @endif
      @if ($darkLogo)
        <img
          class="!h-8 w-auto max-w-[140px] object-contain"
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

  </div>
</header>

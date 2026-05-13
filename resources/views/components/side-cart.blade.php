@if (class_exists('WooCommerce'))
{{--
  Side-cart drawer.
  Outer shell: fixed inset-0, pointer-events-none so the page stays interactive
  when the cart is closed. Backdrop and panel each manage their own x-show.
  cartOpen is owned by the root `app` Alpine component on <html>.
  Opening: dispatched via $dispatch('open-cart') from header buttons, or via
  window.dispatchEvent(new CustomEvent('open-cart')) from JS (AJAX add-to-cart).
  The root <html @open-cart.window> sets cartOpen = true in the app scope.
--}}
<div class="fixed inset-0 z-50 pointer-events-none">

  {{-- Backdrop --}}
  <div
    x-show="cartOpen"
    x-cloak
    class="absolute inset-0 bg-overlay pointer-events-auto"
    @click="closeCart()"
    x-transition:enter="transition ease-smooth duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-smooth duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    aria-hidden="true"
  ></div>

  {{-- Panel --}}
  <div
    x-show="cartOpen"
    x-cloak
    x-trap.noscroll="cartOpen"
    x-ref="sideCartPanel"
    role="dialog"
    aria-modal="true"
    aria-labelledby="side-cart-title"
    class="absolute right-0 top-0 h-full w-full max-w-md bg-surface-1 flex flex-col pointer-events-auto shadow-xl"
    x-transition:enter="transition ease-out-expo duration-500"
    x-transition:enter-start="translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition ease-in-expo duration-300"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="translate-x-full"
    @keydown.escape.window="closeCart()"
  >
    {{-- Panel header --}}
    <div class="flex items-center justify-between px-lg py-md border-b border-border shrink-0">
      <h2 id="side-cart-title" class="text-lg font-semibold text-heading">
        {{ __('Your Cart', 'sobe') }}
      </h2>
      <button
        x-ref="sideCartCloseButton"
        @click="closeCart()"
        class="flex items-center justify-center w-8 h-8 rounded text-text-muted hover:text-text hover:bg-surface-2 transition-colors duration-200"
        aria-label="{{ __('Close cart', 'sobe') }}"
      >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    {{--
      Content area: flex-1 + min-h-0 + flex flex-col allows the inner
      side-cart-content to split into a scrollable items region and a
      sticky footer for the checkout CTA — even after AJAX refresh.
    --}}
    <div
      class="flex-1 min-h-0 flex flex-col"
      x-data="{ refresh() { const p = window.themeCartParams ?? {}; const nonce = p.storeApiNonce ?? ''; const action = p.ajaxAction ?? 'sobe_refresh_cart'; fetch((p.ajaxUrl ?? '{{ admin_url('admin-ajax.php') }}') + '?action=' + encodeURIComponent(action) + '&_wpnonce=' + encodeURIComponent(nonce), { credentials: 'same-origin' }).then(r => r.text()).then(html => { this.$el.innerHTML = html; if (window.Alpine) Alpine.initTree(this.$el) }) } }"
      @cart-updated.window="setTimeout(() => refresh(), 100)"
    >
      @include('partials.side-cart-content')
    </div>
  </div>

</div>
@endif

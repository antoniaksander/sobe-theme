@php
  $cart = function_exists('WC') ? WC()->cart : null;
@endphp

<div class="sobe-side-cart-content flex flex-col h-full">
  @if ($cart && ! $cart->is_empty())

    {{-- Scrollable items list --}}
    <div class="flex-1 min-h-0 overflow-y-auto px-lg py-md">
      <ul role="list" class="flex flex-col divide-y divide-border">
        @foreach ($cart->get_cart() as $cart_item_key => $cart_item)
          @php
            $product          = $cart_item['data'];
            $quantity         = $cart_item['quantity'];
            $display_incl_tax = $cart->display_prices_including_tax();
            $unit_price       = $display_incl_tax
                ? (float) wc_get_price_including_tax($product)
                : (float) wc_get_price_excluding_tax($product);
            $line_price       = $unit_price * $quantity;
          @endphp
          <li class="flex gap-md py-md">
            <a href="{{ $product->get_permalink() }}" class="shrink-0 w-20 h-20 rounded-lg overflow-hidden bg-surface-2" tabindex="-1" aria-hidden="true">
              {!! $product->get_image('woocommerce_thumbnail', ['class' => 'w-full h-full object-cover']) !!}
            </a>

            <div class="flex flex-col flex-1 gap-1 min-w-0">
              <a href="{{ $product->get_permalink() }}" class="text-sm font-semibold text-heading line-clamp-2 hover:text-accent transition-colors duration-200">
                {{ $product->get_name() }}
              </a>
              <p class="text-xs text-text-muted">
                {!! wc_price($unit_price) !!} {{ __('each', 'sobe') }}
              </p>
              <div class="flex items-center gap-1 mt-1">
                <button
                  @click.prevent.stop="updateCartQty('{{ $cart_item_key }}', {{ $quantity - 1 }})"
                  class="w-7 h-7 flex items-center justify-center rounded border border-border text-text hover:bg-surface-2 transition-colors duration-150 text-base leading-none"
                  aria-label="{{ __('Decrease quantity', 'sobe') }}"
                >−</button>
                <input
                  type="number"
                  value="{{ $quantity }}"
                  min="0"
                  @change.prevent.stop="updateCartQty('{{ $cart_item_key }}', parseInt($event.target.value) || 0)"
                  class="w-10 h-7 text-center text-sm border border-border rounded bg-surface-1 text-text focus:outline-none focus:ring-1 focus:ring-ring"
                  aria-label="{{ __('Quantity', 'sobe') }}"
                />
                <button
                  @click.prevent.stop="updateCartQty('{{ $cart_item_key }}', {{ $quantity + 1 }})"
                  class="w-7 h-7 flex items-center justify-center rounded border border-border text-text hover:bg-surface-2 transition-colors duration-150 text-base leading-none"
                  aria-label="{{ __('Increase quantity', 'sobe') }}"
                >+</button>
              </div>
              <button
                @click.prevent.stop="removeFromCart('{{ $cart_item_key }}')"
                class="text-xs text-text-subtle hover:text-accent transition-colors duration-200 mt-auto w-fit remove_from_cart_button"
                aria-label="{{ sprintf(__('Remove %s from cart', 'sobe'), esc_attr($product->get_name())) }}"
              >
                {{ __('Remove', 'sobe') }}
              </button>
            </div>

            <p class="text-sm font-semibold text-heading shrink-0">
              {!! wc_price($line_price) !!}
            </p>
          </li>
        @endforeach
      </ul>
    </div>

    {{-- Sticky checkout footer — always visible, never scrolls away --}}
    <div class="shrink-0 px-lg py-md border-t border-border bg-surface-1 space-y-3">
      <div class="flex items-center justify-between">
        <span class="text-sm text-text-muted">{{ __('Subtotal', 'sobe') }}</span>
        <span class="text-base font-semibold text-heading">{!! $cart->get_cart_subtotal() !!}</span>
      </div>
      <a
        href="{{ wc_get_checkout_url() }}"
        class="flex items-center justify-center w-full py-3 px-lg rounded-lg bg-primary text-primary-fg text-sm font-semibold hover:bg-primary-hover transition-colors duration-200"
      >
        {{ __('Checkout', 'sobe') }}
      </a>
      <a
        href="{{ wc_get_cart_url() }}"
        class="flex items-center justify-center w-full py-2.5 px-lg rounded-lg bg-surface-2 text-text text-sm font-medium hover:bg-surface-3 transition-colors duration-200"
      >
        {{ __('View cart', 'sobe') }}
      </a>
    </div>

  @else

    <div class="flex-1 flex flex-col items-center justify-center gap-md px-lg py-2xl text-center">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="size-12 text-text-subtle" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z" />
      </svg>
      <p class="text-sm text-text-muted">{{ __('Your cart is empty.', 'sobe') }}</p>
      <a href="{{ get_permalink(wc_get_page_id('shop')) }}" class="text-sm font-medium text-accent hover:underline">
        {{ __('Continue shopping', 'sobe') }}
      </a>
    </div>

  @endif
</div>

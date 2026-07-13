@php
  $enabled = (bool) apply_filters(
      'sobe/wishlist/enabled',
      (bool) get_theme_mod(config('theme.prefix').'_header_wishlist', false),
      null
  );
  $provider = apply_filters('sobe/wishlist/provider', class_exists('YITH_WCWL') ? 'yith' : null);
  $url = '#';

  if ($provider === 'yith' && class_exists('YITH_WCWL')) {
      $url = YITH_WCWL()->get_wishlist_url();
  }

  $data = apply_filters('sobe/wishlist/toggle_data', [
      'provider' => $provider,
      'url' => $url,
      'context' => 'header',
  ], 0);

  // Best-effort first paint: correct on an uncached request, and corrected
  // again client-side (resources/js/app.js resyncWishlistCount) for a
  // visitor served a full-page-cached snapshot from before their last
  // wishlist change — same reasoning as the header cart count.
  $initialCount = ($provider === 'yith' && function_exists('yith_wcwl_count_all_products'))
      ? (int) yith_wcwl_count_all_products()
      : 0;
@endphp

@if($enabled && $provider)
  <div
    class="relative"
    x-data="{ wishlistCount: {{ $initialCount }} }"
    @wishlist-updated.window="wishlistCount = $event.detail.count"
  >
    <a href="{!! esc_url($data['url'] ?? $url) !!}"
       aria-label="{{ __('Wishlist', 'sobe') }}"
       class="flex items-center justify-center w-10 h-10 rounded-lg text-text hover:bg-surface-2 transition-colors duration-200">
      <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24"
           stroke="currentColor" stroke-width="1.5" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M4.318 6.318a4.5 4.5 0 016.364 0L12 7.636l1.318-1.318a4.5 4.5 0 116.364 6.364L12 20.364l-7.682-7.682a4.5 4.5 0 010-6.364z"/>
      </svg>
      <span
        class="sobe-wishlist-count absolute -top-1 -right-1 size-4 flex items-center justify-center rounded-full bg-accent text-accent-fg text-[10px] font-bold leading-none"
        :class="wishlistCount > 0 ? '' : 'hidden'"
        x-text="wishlistCount"
        aria-live="polite"
        aria-label="{{ __('Wishlist item count', 'sobe') }}"
      ></span>
    </a>
  </div>
@endif

{{--
Template for displaying product content in the loop.

@see https://docs.woocommerce.com/document/template-structure/
@package WooCommerce/Templates
@version 3.6.0
--}}

@php
  global $product;
  $currentView = 'woocommerce.content-product';
  $productCardView = $product instanceof \WC_Product
      ? apply_filters('sobe/shop_loop/product_card_view', $currentView, $product)
      : $currentView;
@endphp

@if ($productCardView !== $currentView)
  @include($productCardView, ['product' => $product])
@else
@php
  $pfx = config('theme.prefix');
  $data = apply_filters('sobe/shop_loop/product_card_data', [
      'hover' => get_theme_mod("{$pfx}_product_card_hover", 'zoom'),
      'gallery_ids' => $product ? $product->get_gallery_image_ids() : [],
      'permalink' => $product ? $product->get_permalink() : '',
      'title' => $product ? $product->get_name() : '',
      'is_on_sale' => $product ? $product->is_on_sale() : false,
  ], $product);

  $hover = $data['hover'] ?? 'zoom';
  $galleryIds = $data['gallery_ids'] ?? [];
  $permalink = $data['permalink'] ?? '';
  $wishlistHtml = '';

  if ($product instanceof \WC_Product && apply_filters('sobe/wishlist/enabled', true, $product->get_id())) {
      $provider = apply_filters('sobe/wishlist/provider', class_exists('YITH_WCWL') ? 'yith' : null);
      $wishlistData = apply_filters('sobe/wishlist/toggle_data', [
          'provider' => $provider,
          'active' => apply_filters('sobe/wishlist/is_active', false, $product->get_id(), get_current_user_id()),
          'context' => 'product_card',
      ], $product->get_id());
      $wishlistHtml = apply_filters('sobe/wishlist/toggle_html', '', $product->get_id(), $wishlistData);

      if ($wishlistHtml === '' && $provider === 'yith' && shortcode_exists('yith_wcwl_add_to_wishlist')) {
          $wishlistHtml = do_shortcode('[yith_wcwl_add_to_wishlist product_id="' . $product->get_id() . '"]');
      }
  }
@endphp

<li @php echo wc_product_class('group', $product) @endphp>

  {{-- Zone A: Image Shell ────────────────────────────────────────────────── --}}
  <div class="relative">

    <a href="{{ $permalink }}" class="block aspect-square overflow-hidden">
      @if ($hover === 'swap' && ! empty($galleryIds))
        <div class="sobe-product-image-wrapper">
          {!! woocommerce_get_product_thumbnail() !!}
          {!! wp_get_attachment_image($galleryIds[0], 'woocommerce_thumbnail', false, ['class' => 'sobe-secondary-image', 'aria-hidden' => 'true']) !!}
        </div>
      @else
        {!! woocommerce_get_product_thumbnail() !!}
      @endif
    </a>

    {{-- Overlay: absolutely positioned sibling of <a>.
         Shell is pointer-events-none; interactive children opt in with pointer-events-auto. --}}
    <div class="absolute inset-0 pointer-events-none">

      @if (! empty($data['is_on_sale']))
        <span class="onsale pointer-events-none">{{ __('SALE', 'sobe') }}</span>
      @endif

<div class="pointer-events-auto">

  @if ($wishlistHtml)
    <div class="sobe-wishlist-wrapper absolute top-0 right-0 z-20">
      {!! $wishlistHtml !!}
    </div>
  @endif

  @php
    do_action('woocommerce_before_shop_loop_item');
    do_action('woocommerce_before_shop_loop_item_title');
  @endphp
</div>

    </div>
  </div>
  {{-- /Zone A --}}

  {{-- Zone B: Info Shell ──────────────────────────────────────────────────── --}}
  <div class="flex flex-col">
    @php do_action('woocommerce_shop_loop_item_title'); @endphp
    <a href="{{ $permalink }}">
      <h2 class="woocommerce-loop-product__title">{{ $data['title'] ?? '' }}</h2>
    </a>
    @php do_action('woocommerce_after_shop_loop_item_title'); @endphp
  </div>
  {{-- /Zone B --}}

  {{-- Zone C: Action Shell ────────────────────────────────────────────────── --}}
  <div class="mt-auto">
    @php do_action('woocommerce_after_shop_loop_item'); @endphp
  </div>
  {{-- /Zone C --}}

</li>
@endif

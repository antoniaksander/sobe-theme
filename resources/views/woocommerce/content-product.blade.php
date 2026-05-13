{{--
Template for displaying product content in the loop.

@see https://docs.woocommerce.com/document/template-structure/
@package WooCommerce/Templates
@version 3.6.0
--}}

@php
  global $product;
  $pfx        = config('theme.prefix');
  $hover      = get_theme_mod("{$pfx}_product_card_hover", 'zoom');
  $galleryIds = $product ? $product->get_gallery_image_ids() : [];
  $permalink  = $product ? $product->get_permalink() : '';
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

      @if ($product && $product->is_on_sale())
        <span class="onsale pointer-events-none">{{ __('SALE', 'sobe') }}</span>
      @endif

<div class="pointer-events-auto">
  
  {{-- Custom YITH Wrapper (Only renders if the plugin is active) --}}
  @if (shortcode_exists('yith_wcwl_add_to_wishlist'))
    <div class="sobe-wishlist-wrapper absolute top-0 right-0 z-20">
      {!! do_shortcode('[yith_wcwl_add_to_wishlist product_id="' . $product->get_id() . '"]') !!}
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
      <h2 class="woocommerce-loop-product__title">{{ $product ? $product->get_name() : '' }}</h2>
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

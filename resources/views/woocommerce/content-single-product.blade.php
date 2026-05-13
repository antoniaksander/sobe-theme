{{--
Template for displaying a single product.

@see https://docs.woocommerce.com/document/template-structure/
@package WooCommerce/Templates
@version 3.6.0
--}}

@php
  global $product;
  if (! is_a($product, 'WC_Product')) {
      $product = wc_get_product(get_the_ID());
  }
@endphp

@php do_action('woocommerce_before_single_product'); @endphp

@if (post_password_required())
  {!! get_the_password_form() !!}
@elseif ($product)

<div id="product-{{ get_the_ID() }}" @php echo wc_product_class('pdp-grid', $product) @endphp>

  {{-- Row 1, Col 1: Gallery (Tier 1 — Swiper slider) ──────────────────────── --}}
  <div class="pdp-gallery">

    @php
      $mainImageId  = $product->get_image_id();
      $galleryIds   = $product->get_gallery_image_ids();
      $allImageIds  = array_values(array_filter(array_merge([$mainImageId], $galleryIds)));
    @endphp

    {{-- Main Swiper slider --}}
    <div id="pdp-swiper-main" class="swiper pdp-swiper-main"
         aria-label="{{ __('Product images', 'sobe') }}">
      <div class="swiper-wrapper">
        @foreach ($allImageIds as $i => $imgId)
          @php $full = wp_get_attachment_image_url($imgId, 'full'); @endphp
          <div class="swiper-slide" data-full="{{ $full }}">
            {!! wp_get_attachment_image($imgId, 'woocommerce_single', false, [
                'loading'       => $i === 0 ? 'eager' : 'lazy',
                'fetchpriority' => $i === 0 ? 'high'  : 'auto',
            ]) !!}
          </div>
        @endforeach
      </div>
      <div class="swiper-button-prev" aria-label="{{ __('Previous image', 'sobe') }}"></div>
      <div class="swiper-button-next" aria-label="{{ __('Next image', 'sobe') }}"></div>
      <div class="swiper-pagination"></div>
    </div>

    {{-- Thumbnail nav strip --}}
    <div id="pdp-swiper-thumbs" class="swiper pdp-swiper-thumbs"
         aria-label="{{ __('Product image thumbnails', 'sobe') }}">
      <div class="swiper-wrapper">
        @foreach ($allImageIds as $imgId)
          <div class="swiper-slide">
            {!! wp_get_attachment_image($imgId, 'woocommerce_thumbnail') !!}
          </div>
        @endforeach
      </div>
    </div>

    {{-- Hook bus stays open for plugins (sale flash, badges, etc.) --}}
    @php do_action('woocommerce_before_single_product_summary'); @endphp

  </div>

  {{-- Row 1, Col 2: Summary ───────────────────────────────────────────────── --}}
  <div class="pdp-summary summary entry-summary flex flex-col">

    {{-- Brand label --}}
    @php $brands = get_the_terms($product->get_id(), 'product_brand'); @endphp
    @if ($brands && ! is_wp_error($brands))
      <p class="text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">
        {{ $brands[0]->name }}
      </p>
    @endif

    {{-- Product title --}}
    <h1 class="text-3xl lg:text-4xl font-light text-gray-900 dark:text-white mb-4">
      {{ $product->get_name() }}
    </h1>

    {{-- Rating, Price, Variants, Quantity, Add to Cart, Meta (SKU/cats/tags/brand) --}}
    @php do_action('woocommerce_single_product_summary'); @endphp

  </div>

  {{-- Row 2, Col 1: Short Description ─────────────────────────────────────── --}}
  <div class="pdp-short-desc">
    @if ($product->get_short_description())
      <div class="prose prose-sm max-w-none text-gray-600">
        {!! wc_format_content($product->get_short_description()) !!}
      </div>
    @endif
  </div>

  {{-- Row 2, Col 2: Accordions ────────────────────────────────────────────── --}}
  <div class="pdp-accordions">
    @php
      $product_tabs = apply_filters('woocommerce_product_tabs', []);
      uasort($product_tabs, fn ($a, $b) => ($a['priority'] ?? 10) <=> ($b['priority'] ?? 10));
    @endphp

    @if (! empty($product_tabs))
      <div class="border-t border-gray-200">
        @foreach ($product_tabs as $key => $product_tab)
          <details class="sobe-accordion group border-b border-gray-200">
            <summary class="flex items-center justify-between py-4 text-sm font-medium uppercase tracking-wider cursor-pointer list-none">
              {{ apply_filters('woocommerce_product_' . $key . '_tab_title', $product_tab['title'], $key) }}
              <span class="transition-transform duration-300 group-open:rotate-180" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="m6 9 6 6 6-6"/>
                </svg>
              </span>
            </summary>
            <div class="pb-6 text-sm text-gray-600 prose prose-sm max-w-none">
              @if (isset($product_tab['callback']))
                @php call_user_func($product_tab['callback'], $key, $product_tab); @endphp
              @endif
            </div>
          </details>
        @endforeach
      </div>
    @endif
  </div>

</div>

{{-- Full-width below the grid: upsells, related products ───────────────────── --}}
@php do_action('woocommerce_after_single_product_summary'); @endphp

@endif

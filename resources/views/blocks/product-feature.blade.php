@php
  // Guard — nothing renders if no valid product is available
  if (! $product) { return; }

  $reversed = ($attributes['layout'] ?? 'product-left') === 'product-right';

  $aspectClass = match ($attributes['imageRatio'] ?? 'original') {
    'square'    => 'aspect-square object-cover',
    'landscape' => 'aspect-[4/3] object-cover',
    default     => '',
  };

  $heading   = $attributes['heading']   ?? '';
  $paragraph = $attributes['paragraph'] ?? '';
  $ctaText   = $attributes['ctaText']   ?? '';
  $ctaUrl    = !empty($attributes['ctaUrl']) ? $attributes['ctaUrl'] : $productUrl;
  $ctaType   = $attributes['ctaType']   ?? 'btn-dark';
  $allowedCtaTypes = ['btn-dark', 'btn-light', 'btn-outline-dark', 'btn-outline-light', 'link-dark', 'link-light'];
  $ctaType   = in_array($ctaType, $allowedCtaTypes, true) ? $ctaType : 'btn-dark';

  $showImage  = $attributes['showProductImage'] ?? true;
  $showTitle  = $attributes['showProductTitle'] ?? true;
  $showPrice  = $attributes['showProductPrice'] ?? true;
  $showBrand  = $attributes['showProductBrand'] ?? true;
  $customBrand = trim($attributes['customBrandText'] ?? '');

  $displayBrand = $customBrand ?: $productBrand;

  $wrapperAttrs = get_block_wrapper_attributes(['class' => 'sobe-product-feature']);
@endphp

<section {!! $wrapperAttrs !!}>
  <div class="grid md:grid-cols-2 items-center gap-xl lg:gap-2xl" data-animate="product-feature">

    {{-- ── Product column ─────────────────────────────────────── --}}
    <div class="{{ $reversed ? 'md:order-2' : '' }}">

      @if ($showImage && $productImage)
        <a href="{{ esc_url($productUrl) }}" class="block overflow-hidden rounded-lg" tabindex="-1" aria-hidden="true">
          <img
            src="{{ esc_url($productImage) }}"
            alt="{{ esc_attr($productImageAlt) }}"
            class="w-full {{ $aspectClass }}"
            loading="lazy"
            decoding="async"
          />
        </a>
      @endif

      <div class="mt-md">

        @if ($showBrand && $displayBrand)
          <p class="text-sm text-text-muted font-medium tracking-wide uppercase mb-xs">
            {{ esc_html($displayBrand) }}
          </p>
        @endif

        @if ($showTitle && $productName)
          <h3 class="text-xl font-semibold text-heading mb-xs">
            <a href="{{ esc_url($productUrl) }}" class="hover:text-accent transition-colors duration-200">
              {{ esc_html($productName) }}
            </a>
          </h3>
        @endif

        @if ($showPrice && $product)
          {{-- get_price_html() returns WooCommerce-sanitized HTML — safe to output directly --}}
          <div class="text-lg font-bold text-accent">
            {!! $product->get_price_html() !!}
          </div>
        @endif

      </div>
    </div>

    {{-- ── Content column ──────────────────────────────────────── --}}
    <div class="{{ $reversed ? 'md:order-1' : '' }} flex flex-col gap-md">

      @if ($heading)
        <h2 class="text-4xl font-heading font-bold text-heading leading-tight tracking-tight">
          {!! wp_kses_post($heading) !!}
        </h2>
      @endif

      @if ($paragraph)
        <div class="text-base text-text leading-relaxed">
          {!! wp_kses_post($paragraph) !!}
        </div>
      @endif

      @if ($ctaText && $ctaUrl)
        <div>
          <x-button :url="esc_url($ctaUrl)" :type="$ctaType">
            {!! wp_kses_post($ctaText) !!}
          </x-button>
        </div>
      @endif

    </div>

  </div>
</section>

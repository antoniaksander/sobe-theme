@php
  $useManualEntry = $attributes['useManualEntry'] ?? false;
  $manualBrands   = $attributes['brands']         ?? [];
  $speed          = $attributes['speed']          ?? '30s';
  $pauseOnHover   = $attributes['pauseOnHover']   ?? true;
  $showImages     = $attributes['showImages']     ?? true;

  // Whitelist speed to prevent CSS-variable injection.
  $allowedSpeeds = ['15s', '20s', '30s', '40s', '60s'];
  $speed = in_array($speed, $allowedSpeeds, true) ? $speed : '30s';

  // ── Build $items array ────────────────────────────────────────────────────
  $items = [];

  $brandTaxonomy = apply_filters('sobe/catalog_filters/brand_taxonomy', 'product_brand');
  if (!$useManualEntry && is_string($brandTaxonomy) && taxonomy_exists($brandTaxonomy)) {
    // WooCommerce path: pull all non-empty product_brand terms.
    $terms = get_terms([
      'taxonomy'   => $brandTaxonomy,
      'hide_empty' => true,
      'orderby'    => 'name',
      'order'      => 'ASC',
    ]);

    if (!is_wp_error($terms)) {
      foreach ($terms as $term) {
        $thumbnailId = (int) get_term_meta($term->term_id, 'thumbnail_id', true);
        $imageUrl    = $thumbnailId ? (string) wp_get_attachment_image_url($thumbnailId, 'full') : '';
        $imageAlt    = $thumbnailId
          ? (string) (get_post_meta($thumbnailId, '_wp_attachment_image_alt', true) ?: $term->name)
          : $term->name;
        $termLink    = get_term_link($term);

        $items[] = [
          'imageUrl' => $imageUrl,
          'imageAlt' => $imageAlt,
          'name'     => $term->name,
          'link'     => is_wp_error($termLink) ? '' : $termLink,
        ];
      }
    }
  } else {
    // Manual-entry path: use the brands attribute array.
    foreach ($manualBrands as $brand) {
      if (empty($brand['imageUrl']) && empty($brand['name'])) continue;
      $items[] = [
        'imageUrl' => $brand['imageUrl'] ?? '',
        'imageAlt' => $brand['imageAlt'] ?? ($brand['name'] ?? ''),
        'name'     => $brand['name']     ?? '',
        'link'     => $brand['link']     ?? '',
      ];
    }
  }

  $wrapperAttrs = get_block_wrapper_attributes([
    'class' => 'brand-carousel brand-carousel--sobe',
    'style' => "--carousel-speed:{$speed}",
  ]);
  $items = apply_filters('sobe/brand_carousel/items', $items, $attributes);
  $view = apply_filters('sobe/brand_carousel/view', '', $items, $attributes);
@endphp

@if(!empty($items))
@if($view)
  @include($view, ['items' => $items, 'attributes' => $attributes])
@else
<section
  {!! $wrapperAttrs !!}
  @if($pauseOnHover) data-pause-on-hover="true" @endif
  data-animate="brand-carousel"
>
  <div class="brand-carousel__viewport relative inset-0 m-0 ">
    <div class="brand-carousel__track" role="list">

      {{-- ── Original track — read by screen readers ───────────────────── --}}
      @foreach($items as $item)
        @php $hasLink = !empty($item['link']); @endphp
        <div class="brand-carousel__item" role="listitem">
          @if($hasLink)
            <a href="{!! esc_url($item['link']) !!}" class="brand-carousel__link" rel="noopener">
          @endif

          @if($showImages && !empty($item['imageUrl']))
<img
  src="{!! esc_url($item['imageUrl']) !!}"
  alt="{!! esc_attr($item['imageAlt']) !!}"
  class="brand-carousel__logo"
  loading="eager"
/>
          @else
            <span class="brand-carousel__name">{!! esc_html($item['name']) !!}</span>
          @endif

          @if($hasLink)</a>@endif
        </div>
      @endforeach

      {{-- ── Duplicate track — hidden from assistive tech, enables seamless loop ── --}}
      @foreach($items as $item)
        @php $hasLink = !empty($item['link']); @endphp
        <div class="brand-carousel__item" aria-hidden="true">
          @if($hasLink)
            <a href="{!! esc_url($item['link']) !!}" class="brand-carousel__link" rel="noopener" tabindex="-1">
          @endif

          @if($showImages && !empty($item['imageUrl']))
<img
  src="{!! esc_url($item['imageUrl']) !!}"
  alt="{!! esc_attr($item['imageAlt']) !!}"
  class="brand-carousel__logo"
  loading="eager"
/>
          @else
            <span class="brand-carousel__name">{!! esc_html($item['name']) !!}</span>
          @endif

          @if($hasLink)</a>@endif
        </div>
      @endforeach

    </div>
  </div>
</section>
@endif
@endif

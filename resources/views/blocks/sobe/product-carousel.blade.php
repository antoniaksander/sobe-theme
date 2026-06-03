@php
  $count      = (int) ($attributes['count']      ?? 8);
  $orderBy    = $attributes['orderBy']    ?? 'latest';
  $categoryId = (int) ($attributes['categoryId'] ?? 0);
  $brandId    = (int) ($attributes['brandId']    ?? 0);
  $heading    = $attributes['heading']    ?? '';
  $paragraph  = $attributes['paragraph'] ?? '';
  $linkText   = $attributes['linkText']  ?? '';
  $linkUrl    = $attributes['linkUrl']   ?? '';
  $linkType   = $attributes['linkType']  ?? 'btn-dark';
  $count = max(1, min($count, 12));

  // Whitelist orderBy to prevent injection.
  $allowedOrderBy = ['latest', 'featured', 'best_selling', 'top_rated', 'on_sale', 'random'];
  $orderBy = in_array($orderBy, $allowedOrderBy, true) ? $orderBy : 'latest';

  // Whitelist linkType.
  $allowedLinkTypes = ['btn-dark', 'btn-light', 'btn-outline-dark', 'btn-outline-light', 'link-dark', 'link-light'];
  $linkType = in_array($linkType, $allowedLinkTypes, true) ? $linkType : 'btn-dark';

  // ── Build WP_Query args ─────────────────────────────────────────────────
  $args = [
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => $count,
  ];

  switch ($orderBy) {
    case 'featured':
      $args['tax_query'][] = [
        'taxonomy' => 'product_visibility',
        'field'    => 'name',
        'terms'    => 'featured',
        'operator' => 'IN',
      ];
      $args['orderby'] = 'date';
      $args['order']   = 'DESC';
      break;

    case 'best_selling':
      $args['meta_key'] = 'total_sales';
      $args['orderby']  = 'meta_value_num';
      $args['order']    = 'DESC';
      break;

    case 'top_rated':
      $args['meta_key'] = '_wc_average_rating';
      $args['orderby']  = 'meta_value_num';
      $args['order']    = 'DESC';
      break;

    case 'on_sale':
      $args['post__in'] = wc_get_product_ids_on_sale() ?: [0];
      $args['orderby']  = 'date';
      $args['order']    = 'DESC';
      break;

    case 'random':
      $args['orderby'] = 'rand';
      break;

    default: // latest
      $args['orderby'] = 'date';
      $args['order']   = 'DESC';
      break;
  }

  // ── Taxonomy filters (each clause ANDed together and with featured) ─────
  if ($categoryId > 0) {
    $args['tax_query'][] = [
      'taxonomy' => 'product_cat',
      'field'    => 'term_id',
      'terms'    => $categoryId,
    ];
  }

  if ($brandId > 0) {
    $brandTaxonomy = apply_filters('sobe/catalog_filters/brand_taxonomy', 'product_brand');
    if (is_string($brandTaxonomy) && taxonomy_exists($brandTaxonomy)) {
      $args['tax_query'][] = [
        'taxonomy' => $brandTaxonomy,
        'field'    => 'term_id',
        'terms'    => $brandId,
      ];
    }
  }

  if (count($args['tax_query'] ?? []) > 1) {
    $args['tax_query']['relation'] = 'AND';
  }

  $args = (array) apply_filters('sobe/shop_loop/query_args', $args, [
    'context' => 'product_carousel',
    'attributes' => $attributes,
  ]);

  $products_query = new \WP_Query($args);

  $wrapperAttrs = get_block_wrapper_attributes(['class' => 'product-carousel product-carousel--sobe woocommerce my-12']);

  $hasHeader = $heading || $paragraph || ($linkText && $linkUrl);
@endphp

@if($products_query->have_posts())
  <section {!! $wrapperAttrs !!} data-animate="fade-up">

    {{-- ── Section header ──────────────────────────────────────────────── --}}
    @if($hasHeader)
      <div class="product-carousel__header flex items-end justify-between gap-6 mb-8 px-4 md:px-0">
        @if($heading || $paragraph)
          <div class="product-carousel__header-text">
            @if($heading)
              <h2 class="product-carousel__heading text-2xl md:text-3xl font-bold text-heading m-0 leading-tight">
                {{ esc_html($heading) }}
              </h2>
            @endif
            @if($paragraph)
              <p class="product-carousel__paragraph text-text-muted mt-2 mb-0 max-w-prose">
                {{ esc_html($paragraph) }}
              </p>
            @endif
          </div>
        @endif
        @if($linkText && $linkUrl)
          <div class="product-carousel__header-cta flex-shrink-0">
            <x-button :url="esc_url($linkUrl)" :type="$linkType">
              {!! wp_kses_post($linkText) !!}
            </x-button>
          </div>
        @endif
      </div>
    @endif

    {{-- ── Swiper ───────────────────────────────────────────────────────── --}}
    <div class="swiper product-carousel-swiper relative overflow-hidden px-4 md:px-0">

      <ul class="swiper-wrapper products m-0 p-0 !flex">
        @while($products_query->have_posts())
          @php
            $products_query->the_post();
          @endphp
          <div class="swiper-slide list-none">
            @php
              global $product;
              if ($product instanceof \WC_Product) {
                do_action('sobe/shop_loop/before_product_card', $product, ['context' => 'product_carousel', 'attributes' => $attributes]);
              }
            @endphp
            @include('woocommerce.content-product')
            @php
              if ($product instanceof \WC_Product) {
                do_action('sobe/shop_loop/after_product_card', $product, ['context' => 'product_carousel', 'attributes' => $attributes]);
              }
            @endphp
          </div>
        @endwhile
      </ul>

      <button class="carousel-btn-prev hidden md:flex absolute left-0 top-1/2 -translate-y-1/2 z-10 size-10 bg-white border border-gray-200 rounded-full items-center justify-center cursor-pointer shadow-sm" aria-label="{{ __('Previous', 'sobe') }}">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
      </button>
      <button class="carousel-btn-next hidden md:flex absolute right-0 top-1/2 -translate-y-1/2 z-10 size-10 bg-white border border-gray-200 rounded-full items-center justify-center cursor-pointer shadow-sm" aria-label="{{ __('Next', 'sobe') }}">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
      </button>

    </div>
  </section>
  @php(wp_reset_postdata())
@endif

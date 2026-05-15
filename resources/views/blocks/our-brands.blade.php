@php
  $showLogos = $attributes['showLogos'] ?? false;
  $hideEmpty = $attributes['hideEmpty'] ?? true;

  $brandTaxonomy = apply_filters('sobe/catalog_filters/brand_taxonomy', 'product_brand');
  $terms = is_string($brandTaxonomy) && taxonomy_exists($brandTaxonomy) ? get_terms([
    'taxonomy'   => $brandTaxonomy,
    'hide_empty' => $hideEmpty,
    'orderby'    => 'name',
    'order'      => 'ASC',
  ]) : [];

  $grouped = [];

  if (!is_wp_error($terms) && !empty($terms)) {
    foreach ($terms as $term) {
      $first = mb_strtoupper(mb_substr($term->name, 0, 1, 'UTF-8'), 'UTF-8');

      if (class_exists('Normalizer')) {
        $nfkd   = \Normalizer::normalize($first, \Normalizer::NFKD);
        $ascii  = preg_replace('/[\x{0300}-\x{036f}]/u', '', $nfkd);
        $letter = strtoupper(substr($ascii, 0, 1));
      } else {
        $letter = $first;
      }

      $key = (ctype_alpha($letter) && strlen($letter) === 1) ? $letter : '0-9';

      $logoId  = $showLogos ? (int) get_term_meta($term->term_id, 'thumbnail_id', true) : 0;
      $logoUrl = $logoId ? (string) wp_get_attachment_image_url($logoId, 'medium') : '';
      $logoAlt = $logoId
        ? (string) (get_post_meta($logoId, '_wp_attachment_image_alt', true) ?: $term->name)
        : $term->name;
      $link = get_term_link($term);

      $grouped[$key][] = [
        'name'    => $term->name,
        'link'    => is_wp_error($link) ? '' : $link,
        'logoUrl' => $logoUrl,
        'logoAlt' => $logoAlt,
      ];
    }
  }

  ksort($grouped);
  if (isset($grouped['0-9'])) {
    $bucket = $grouped['0-9'];
    unset($grouped['0-9']);
    $grouped['0-9'] = $bucket;
  }

  $letters = array_keys($grouped);

  $wrapperAttrs = get_block_wrapper_attributes(['class' => 'sobe-our-brands !px-0']);
  $grouped = apply_filters('sobe/our_brands/grouped', $grouped, $attributes);
  $view = apply_filters('sobe/our_brands/view', '', $grouped, $attributes);
@endphp

@if(!empty($grouped))
@if($view)
  @include($view, ['grouped' => $grouped, 'attributes' => $attributes])
@else
<section {!! $wrapperAttrs !!} data-block="our-brands">

  <nav class="brands-alpha-nav border-b border-border flex flex-wrap gap-x-3 gap-y-1 px-6 lg:px-8 py-3"
       aria-label="{{ __('Brand alphabet navigation', 'sobe') }}">
    @foreach($letters as $letter)
      <a class="brands-alpha-nav__letter text-sm tracking-wider text-text-muted no-underline pb-1 border-b-2 border-transparent transition-colors duration-200 hover:text-text [&.is-active]:text-text [&.is-active]:border-accent"
         href="#brands-section-{{ esc_attr($letter) }}"
         data-letter="{{ esc_attr($letter) }}">
        {{ esc_html($letter) }}
      </a>
    @endforeach
  </nav>

  @foreach($grouped as $letter => $brands)
    <div id="brands-section-{{ esc_attr($letter) }}"
         class="brands-section [scroll-margin-top:8rem] px-6 lg:px-8"
         data-section="{{ esc_attr($letter) }}">

      <h2 class="text-7xl font-bold text-heading leading-none mt-10 lg:mt-14 mb-6 lg:mb-8" aria-hidden="true">
        {{ esc_html($letter) }}
      </h2>

      <ul class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-x-xl gap-y-md list-none p-0 mb-3xl" role="list">
        @foreach($brands as $brand)
          <li>
            @if(!empty($brand['link']))
              <a href="{{ esc_url($brand['link']) }}"
                 class="flex flex-col gap-xs text-text no-underline text-sm transition-colors duration-200 hover:text-accent">
            @else
              <span class="flex flex-col gap-xs text-text text-sm">
            @endif

            @if($showLogos && !empty($brand['logoUrl']))
              <img
                src="{{ esc_url($brand['logoUrl']) }}"
                alt="{{ esc_attr($brand['logoAlt']) }}"
                class="h-8 w-auto max-w-24 object-contain object-left"
                loading="lazy"
              />
            @endif
            <span>{{ esc_html($brand['name']) }}</span>

            @if(!empty($brand['link']))
              </a>
            @else
              </span>
            @endif
          </li>
        @endforeach
      </ul>
    </div>
  @endforeach

</section>
@endif
@endif

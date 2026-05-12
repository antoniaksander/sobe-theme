@php
  $layout = $attributes['layout'] ?? 'bento-alternating';
  // Legacy attribute from removed "stack" layout — map to two-column grid.
  if ($layout === 'stack') {
    $layout = 'uniform-2';
  }

  $allowedLayouts = [
    'bento-alternating',
    'uniform-2',
    'columns-4',
    'hero-follow',
    'split-tall-left',
  ];
  if (! in_array($layout, $allowedLayouts, true)) {
    $layout = 'bento-alternating';
  }

  $enableHover = ($attributes['enableHoverEffects'] ?? true) !== false;
  $count = count($categories);

  $heading = trim($attributes['heading'] ?? '');
  $paragraph = trim($attributes['paragraph'] ?? '');
  $hasHeader = $heading !== '' || $paragraph !== '';

  if ($count === 0 && ! $hasHeader) {
    return;
  }

  $layoutClass = 'sobe-product-categories-grid--layout-' . $layout;
  $itemsClass = $count > 0
    ? 'sobe-product-categories-grid--items-' . min($count, 12)
    : '';

  $wrapperAttrs = get_block_wrapper_attributes([
    'class' => trim('sobe-product-categories-grid bg-background ' . $layoutClass . ' ' . $itemsClass),
  ]);

  $gridId = wp_unique_id('sobe-pc-grid-');
@endphp

<section {!! $wrapperAttrs !!} data-animate="fade-up">
  @if ($hasHeader)
    <header class="sobe-product-categories-grid__header mb-8 px-4 md:px-0">
      @if ($heading !== '')
        <h2 class="text-2xl md:text-3xl font-bold text-heading m-0 leading-tight">
          {{ esc_html($heading) }}
        </h2>
      @endif
      @if ($paragraph !== '')
        <p class="text-text-muted mt-2 mb-0 max-w-prose">
          {{ esc_html($paragraph) }}
        </p>
      @endif
    </header>
  @endif

  @if ($count > 0)
    <div class="sobe-product-categories-grid__inner">
      <button
        type="button"
        class="sobe-pc-grid-nav sobe-pc-grid-nav--prev"
        aria-controls="{{ esc_attr($gridId) }}"
        aria-label="{{ esc_attr(__('Previous category', 'sobe')) }}"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
      </button>
      <button
        type="button"
        class="sobe-pc-grid-nav sobe-pc-grid-nav--next"
        aria-controls="{{ esc_attr($gridId) }}"
        aria-label="{{ esc_attr(__('Next category', 'sobe')) }}"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
      </button>

      <div class="swiper sobe-pc-grid-swiper">
        <ul id="{{ esc_attr($gridId) }}" class="swiper-wrapper sobe-product-categories-grid__list">
          @foreach ($categories as $cat)
            @php
              $hasImage = $cat['imageUrl'] !== '';
              $hoverClass = $enableHover ? 'sobe-pc-card--hoverable' : '';
            @endphp
            <li class="swiper-slide sobe-pc-card {{ $hoverClass }}">
              <a
                href="{{ esc_url($cat['link']) }}"
                class="sobe-pc-card__link focus-visible:outline-none"
              >
                <span class="sobe-pc-card__media">
                  @if ($hasImage)
                    <img
                      src="{{ esc_url($cat['imageUrl']) }}"
                      alt="{{ esc_attr($cat['imageAlt']) }}"
                      class="sobe-pc-card__img"
                      loading="lazy"
                      decoding="async"
                    />
                  @else
                    <span class="sobe-pc-card__placeholder" aria-hidden="true"></span>
                  @endif
                  <span class="sobe-pc-card__scrim sobe-pc-card__scrim--base" aria-hidden="true"></span>
                  @if ($enableHover)
                    <span class="sobe-pc-card__scrim sobe-pc-card__scrim--hover" aria-hidden="true"></span>
                  @endif
                </span>
                <span class="sobe-pc-card__text">
                  <span class="sobe-pc-card__count">
                    {{ sprintf(
                      /* translators: %d: number of products in the category */
                      _n('%d product', '%d products', $cat['count'], 'sobe'),
                      number_format_i18n($cat['count'])
                    ) }}
                  </span>
                  <span class="sobe-pc-card__name">{{ esc_html($cat['name']) }}</span>
                </span>
              </a>
            </li>
          @endforeach
        </ul>
      </div>
    </div>
  @elseif ($hasHeader)
    <p class="text-text-muted mb-0 px-4 md:px-0">
      {{ __('Add categories in the block editor to show the grid.', 'sobe') }}
    </p>
  @endif
</section>

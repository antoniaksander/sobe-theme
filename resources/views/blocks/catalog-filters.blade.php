@php
  $activeCatSlug = $activeFilters['product_cat'] ?? '';
  $instanceId = wp_unique_id('sobe-catalog-filters-');
  $drawerId = "{$instanceId}-drawer";
  $drawerTitleId = "{$instanceId}-drawer-title";
@endphp

<div
  class="sobe-catalog-filters-block"
  data-catalog-filters-instance="{{ esc_attr($instanceId) }}"
>
  <button
    class="sobe-filter-mobile-trigger"
    data-catalog-filters-open
    type="button"
    aria-expanded="false"
    aria-controls="{{ esc_attr($drawerId) }}"
    hidden
  >
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4" aria-hidden="true">
      <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
    </svg>
    {{ __('Filter', 'sobe') }}
  </button>

  <div data-catalog-filters-desktop>
    <div
      class="sobe-catalog-filters"
      data-catalog-filters
      aria-label="{{ __('Product filters', 'sobe') }}"
    >

  {{-- Active filter chips --}}
  <div class="sobe-filter-chips" data-filter-chips aria-label="{{ __('Active filters', 'sobe') }}">
    @if ($activeCatSlug)
      @php $catTerm = get_term_by('slug', $activeCatSlug, 'product_cat'); @endphp
      @if ($catTerm)
        <button
          class="sobe-filter-chip"
          data-remove-filter="product_cat"
          data-remove-value="{{ esc_attr($activeCatSlug) }}"
          aria-label="{{ __('Remove', 'sobe') }} {{ $catTerm->name }}"
        >
          {{ $catTerm->name }}
          <span class="sobe-filter-chip__remove" aria-hidden="true">×</span>
        </button>
      @endif
    @endif

    @foreach ($activeFilters as $filterKey => $filterVal)
      @if ($filterKey === 'product_cat' || $filterKey === 'min_price' || $filterKey === 'max_price')
        @continue
      @endif
      @foreach ((array) $filterVal as $slug)
        <button
          class="sobe-filter-chip"
          data-remove-filter="{{ esc_attr($filterKey) }}"
          data-remove-value="{{ esc_attr($slug) }}"
          aria-label="{{ __('Remove', 'sobe') }} {{ esc_html($slug) }}"
        >
          {{ esc_html($slug) }}
          <span class="sobe-filter-chip__remove" aria-hidden="true">×</span>
        </button>
      @endforeach
    @endforeach
  </div>

  {{-- Clear all active filters --}}
  <button
    class="sobe-filter-clear-all"
    data-clear-all-filters
    type="button"
    hidden
  >{{ __('Clear all', 'sobe') }}</button>

  {{-- Price type (accordion, radio) --}}
  <details class="sobe-accordion" @if (!$collapseByDefault) open @endif>
    <summary class="sobe-accordion__trigger">{{ __('Price type', 'sobe') }}</summary>
    <div class="sobe-accordion__panel">
      <ul class="sobe-filter-list" role="radiogroup">
        @foreach([
          'all'        => __('All', 'sobe'),
          'on_sale'    => __('On sale', 'sobe'),
          'full_price' => __('Full price', 'sobe'),
        ] as $val => $label)
          <li class="sobe-filter-list__item">
            <label class="sobe-radio">
              <input
                type="radio"
                name="price_type"
                value="{{ $val }}"
                class="sobe-radio__input"
                @if(($activeFilters['price_type'] ?? 'all') === $val) checked @endif
              >
              <span class="sobe-radio__label">{{ $label }}</span>
            </label>
          </li>
        @endforeach
      </ul>
    </div>
  </details>

  {{-- Categories (single-select radio) --}}
  @if ($showCategories && !empty($categories))
  <details class="sobe-accordion" @if (!$collapseByDefault) open @endif>
    <summary class="sobe-accordion__trigger">{{ __('Categories', 'sobe') }}</summary>
    <div class="sobe-accordion__panel">
      @if (count($categories) > 5)
        <div class="sobe-filter-search">
          <input
            type="search"
            class="sobe-filter-search__input"
            placeholder="{{ __('Search categories…', 'sobe') }}"
            data-filter-search="categories"
            aria-label="{{ __('Search categories', 'sobe') }}"
          >
        </div>
      @endif
      <ul class="sobe-filter-list" role="radiogroup" data-filter-list="categories">
        @foreach ($categories as $cat)
          <li class="sobe-filter-list__item">
            <label class="sobe-radio">
              <input
                type="radio"
                name="product_cat"
                value="{{ esc_attr($cat->slug) }}"
                class="sobe-radio__input"
                @if ($activeCatSlug === $cat->slug) checked @endif
              >
              <span class="sobe-radio__label">{{ esc_html($cat->name) }}</span>
              <span class="sobe-filter-count">({{ $cat->count }})</span>
            </label>
          </li>
        @endforeach
      </ul>
    </div>
  </details>
  @endif

  {{-- Brands (multi-select checkbox) --}}
  @if ($showBrands && !empty($brands))
  <details class="sobe-accordion sobe-accordion--scrollable" @if ($brandsOpenByDefault) open @endif>
    <summary class="sobe-accordion__trigger">{{ __('Brands', 'sobe') }}</summary>
    <div class="sobe-accordion__panel">
      @if (count($brands) > 5)
        <div class="sobe-filter-search">
          <input
            type="search"
            class="sobe-filter-search__input"
            placeholder="{{ __('Search brands…', 'sobe') }}"
            data-filter-search="brands"
            aria-label="{{ __('Search brands', 'sobe') }}"
          >
        </div>
      @endif
      <ul class="sobe-filter-list" data-filter-list="brands" role="group" aria-label="{{ __('Brands', 'sobe') }}">
        @php $activeBrands = (array) ($activeFilters[$brandsTaxonomy] ?? []); @endphp
        @foreach ($brands as $brand)
          <li class="sobe-filter-list__item">
            <label class="sobe-checkbox">
              <input
                type="checkbox"
                name="{{ esc_attr($brandsTaxonomy) }}[]"
                value="{{ esc_attr($brand->slug) }}"
                class="sobe-checkbox__input"
                @if (in_array($brand->slug, $activeBrands, true)) checked @endif
              >
              <span class="sobe-checkbox__label">{{ esc_html($brand->name) }}</span>
              <span class="sobe-filter-count">({{ $brand->count }})</span>
            </label>
          </li>
        @endforeach
      </ul>
    </div>
  </details>
  @endif

  {{-- Attribute accordions --}}
  @if ($showAttributes)
    @foreach ($attributeGroups as $group)
    @php
      $attrKey    = 'pa_' . $group->attribute_name;
      $activeVals = (array) ($activeFilters[$group->attribute_name] ?? []);
    @endphp
    <details class="sobe-accordion" @if (!$collapseByDefault) open @endif>
      <summary class="sobe-accordion__trigger">{{ esc_html($group->attribute_label) }}</summary>
      <div class="sobe-accordion__panel">
        @if ($group->attribute_type === 'color')
          <div class="sobe-swatches" role="group" aria-label="{{ esc_attr($group->attribute_label) }}">
            @foreach ($group->terms as $term)
              @php $hex = \App\sobe_get_swatch_value($term, $group->attribute_name); @endphp
              @if ($hex)
                <label
                  class="sobe-swatch sobe-swatch--color {{ in_array($term->slug, $activeVals, true) ? 'is-active' : '' }}"
                  style="--swatch-color: {{ esc_attr($hex) }}"
                  title="{{ esc_attr($term->name) }}"
                >
                  <input
                    type="checkbox"
                    class="sr-only"
                    name="filter_{{ esc_attr($group->attribute_name) }}[]"
                    value="{{ esc_attr($term->slug) }}"
                    @if (in_array($term->slug, $activeVals, true)) checked @endif
                  >
                </label>
              @else
                <label class="sobe-checkbox">
                  <input
                    type="checkbox"
                    name="filter_{{ esc_attr($group->attribute_name) }}[]"
                    value="{{ esc_attr($term->slug) }}"
                    class="sobe-checkbox__input"
                    @if (in_array($term->slug, $activeVals, true)) checked @endif
                  >
                  <span class="sobe-checkbox__label">{{ esc_html($term->name) }}</span>
                </label>
              @endif
            @endforeach
          </div>
        @else
          @if (count($group->terms) > 5)
            <div class="sobe-filter-search">
              <input
                type="search"
                class="sobe-filter-search__input"
                placeholder="{{ __('Search…', 'sobe') }}"
                data-filter-search="{{ esc_attr($attrKey) }}"
                aria-label="{{ __('Search', 'sobe') }} {{ esc_attr($group->attribute_label) }}"
              >
            </div>
          @endif
          <ul class="sobe-filter-list" data-filter-list="{{ esc_attr($attrKey) }}" role="group" aria-label="{{ esc_attr($group->attribute_label) }}">
            @foreach ($group->terms as $term)
              <li class="sobe-filter-list__item">
                <label class="sobe-checkbox">
                  <input
                    type="checkbox"
                    name="filter_{{ esc_attr($group->attribute_name) }}[]"
                    value="{{ esc_attr($term->slug) }}"
                    class="sobe-checkbox__input"
                    @if (in_array($term->slug, $activeVals, true)) checked @endif
                  >
                  <span class="sobe-checkbox__label">{{ esc_html($term->name) }}</span>
                  <span class="sobe-filter-count">({{ $term->count }})</span>
                </label>
              </li>
            @endforeach
          </ul>
        @endif
      </div>
    </details>
    @endforeach
  @endif

  {{-- Price range --}}
  @if ($showPriceRange)
  <details class="sobe-accordion" @if (!$collapseByDefault) open @endif>
    <summary class="sobe-accordion__trigger">{{ __('Price', 'sobe') }}</summary>
    <div class="sobe-accordion__panel">
      <div
        class="sobe-range-slider"
        data-range-slider
        data-min="{{ $priceRange->min }}"
        data-max="{{ $priceRange->max }}"
        data-from="{{ $activeFilters['min_price'] ?? $priceRange->min }}"
        data-to="{{ $activeFilters['max_price'] ?? $priceRange->max }}"
      ></div>
      <div class="sobe-range-slider__inputs">
        <input
          type="number"
          class="sobe-range-slider__input"
          data-price-min
          min="{{ $priceRange->min }}"
          max="{{ $priceRange->max }}"
          value="{{ $activeFilters['min_price'] ?? $priceRange->min }}"
          aria-label="{{ __('Minimum price', 'sobe') }}"
        >
        <span aria-hidden="true">—</span>
        <input
          type="number"
          class="sobe-range-slider__input"
          data-price-max
          min="{{ $priceRange->min }}"
          max="{{ $priceRange->max }}"
          value="{{ $activeFilters['max_price'] ?? $priceRange->max }}"
          aria-label="{{ __('Maximum price', 'sobe') }}"
        >
      </div>
    </div>
  </details>
  @endif

    </div>
  </div>

  <div
    id="{{ esc_attr($drawerId) }}"
    class="sobe-filter-drawer"
    data-catalog-filters-drawer
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ esc_attr($drawerTitleId) }}"
    hidden
  >
    <div class="sobe-filter-drawer__header">
      <span id="{{ esc_attr($drawerTitleId) }}">{{ __('Filter products', 'sobe') }}</span>
      <button
        class="sobe-filter-drawer__close"
        data-catalog-filters-close
        type="button"
        aria-label="{{ __('Close filters', 'sobe') }}"
      >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    <div class="sobe-filter-drawer__body" data-catalog-filters-drawer-body></div>
    <div class="sobe-filter-drawer__footer">
      <button
        class="sobe-filter-drawer__apply"
        data-catalog-filters-close
        type="button"
      >{{ __('Close', 'sobe') }}</button>
    </div>
  </div>
</div>

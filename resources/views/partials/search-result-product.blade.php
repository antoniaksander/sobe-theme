@php $product = wc_get_product(get_the_ID()); @endphp
@if ($product)
<article class="search-result-card search-result-card--product">
  <a href="{{ get_permalink() }}" class="search-result-card__image-link" tabindex="-1" aria-hidden="true">
    {!! woocommerce_get_product_thumbnail('woocommerce_thumbnail') !!}
  </a>
  <div class="search-result-card__body">
    <span class="search-result-card__type">{{ __('Product', 'sobe') }}</span>
    <h2 class="search-result-card__title">
      <a href="{{ get_permalink() }}">{!! get_the_title() !!}</a>
    </h2>
    <div class="search-result-card__price">{!! $product->get_price_html() !!}</div>
  </div>
</article>
@endif

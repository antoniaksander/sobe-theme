@php
  global $product;
  $product = wc_get_product(get_the_ID());
@endphp

@if ($product instanceof \WC_Product)
  @include('woocommerce.content-product')
@endif

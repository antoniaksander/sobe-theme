@if($related_products)
  @php
    if (function_exists('wp_increase_content_media_count')) {
      $count = wp_increase_content_media_count(0);
      if ($count < wp_omit_loading_attr_threshold()) {
        wp_increase_content_media_count(wp_omit_loading_attr_threshold() - $count);
      }
    }
    global $product;
    $heading = apply_filters('woocommerce_product_related_products_heading', __('Related products', 'woocommerce'));
    $heading = apply_filters('sobe/related_products/heading', $heading, $product);
  @endphp

  <section class="related products sobe-pdp-section">
    @if($heading)
      <h2 class="sobe-pdp-section__heading">{{ $heading }}</h2>
    @endif

    @php woocommerce_product_loop_start(); @endphp

    @foreach($related_products as $related_product)
      @php
        $post_object = get_post($related_product->get_id());
        setup_postdata($GLOBALS['post'] = $post_object);
        wc_get_template_part('content', 'product');
      @endphp
    @endforeach

    @php woocommerce_product_loop_end(); @endphp
  </section>

  @php wp_reset_postdata(); @endphp
@endif

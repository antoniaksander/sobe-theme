@if($upsells)
  @php
    $heading = apply_filters('woocommerce_product_upsells_products_heading', __('You may also like&hellip;', 'woocommerce'));
  @endphp

  <section class="up-sells upsells products sobe-pdp-section">
    @if($heading)
      <h2 class="sobe-pdp-section__heading">{!! $heading !!}</h2>
    @endif

    @php woocommerce_product_loop_start(); @endphp

    @foreach($upsells as $upsell)
      @php
        $post_object = get_post($upsell->get_id());
        setup_postdata($GLOBALS['post'] = $post_object);
        wc_get_template_part('content', 'product');
      @endphp
    @endforeach

    @php woocommerce_product_loop_end(); @endphp
  </section>

  @php wp_reset_postdata(); @endphp
@endif

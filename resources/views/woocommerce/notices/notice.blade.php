@if (! empty($notices))
  <div class="woocommerce-info" role="status">
    @foreach ($notices as $notice)
      {!! wc_kses_notice($notice['notice'] ?? '') !!}
    @endforeach
  </div>
@endif

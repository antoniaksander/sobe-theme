@if (! empty($notices))
  <ul class="woocommerce-error" role="alert">
    @foreach ($notices as $notice)
      <li>{!! wc_kses_notice($notice['notice'] ?? '') !!}</li>
    @endforeach
  </ul>
@endif

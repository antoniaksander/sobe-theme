@if (! empty($messages))
  <div class="woocommerce-message" role="alert">
    @foreach ($messages as $message)
      {!! wc_kses_notice($message['notice'] ?? '') !!}
    @endforeach
  </div>
@endif

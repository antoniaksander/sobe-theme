@php
  $content = $attributes['content'] ?? __('Infrastructure block', config('theme.textdomain'));
  $wrapperAttrs = get_block_wrapper_attributes(['class' => 'wp-example-block']);
@endphp

<section {!! $wrapperAttrs !!}>
  {!! wp_kses_post($content) !!}
</section>

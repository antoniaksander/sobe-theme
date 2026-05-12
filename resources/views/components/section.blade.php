@props([
    'id'        => null,
    'container' => true,
    'padding'   => 'default',
    'width'     => 'standard',
])

@php
  $paddingMap = [
      'none'    => '',
      'sm'      => 'py-8 md:py-12',
      'default' => 'py-16 md:py-24',
      'lg'      => 'py-24 md:py-32',
      'xl'      => 'py-32 md:py-40',
      'hero'    => 'pt-8 md:pt-12 pb-16 md:pb-24',
  ];

  $widthMap = [
      'reading'  => 'max-w-reading',
      'standard' => 'max-w-standard',
      'grid'     => 'max-w-grid',
  ];

  $paddingClass = $paddingMap[$padding] ?? $paddingMap['default'];
  $widthClass   = $widthMap[$width]    ?? $widthMap['standard'];
@endphp

<section
  @if($id) id="{{ $id }}" @endif
  {{ $attributes->merge(['class' => $paddingClass]) }}
>
  @if($container)
    <div class="{{ $widthClass }} mx-auto w-full px-6 lg:px-8">
      {{ $slot }}
    </div>
  @else
    {{ $slot }}
  @endif
</section>

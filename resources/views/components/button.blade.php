@props([
    'type'       => 'primary',
    'url'        => '#',
    'isExternal' => false,
])

@php
  $base = 'inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg font-medium text-sm tracking-wide transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring';

  $variants = [
      'primary'          => $base . ' bg-primary text-primary-fg hover:bg-primary-hover',
      'secondary'        => $base . ' bg-surface-2 text-text hover:bg-surface-3 border border-border',
      'outline'          => $base . ' bg-transparent text-text hover:bg-surface-2 border border-border',
      'btn-dark'         => 'btn btn-dark',
      'btn-light'        => 'btn btn-light',
      'btn-outline-dark' => 'btn btn-outline-dark',
      'btn-outline-light'=> 'btn btn-outline-light',
      'link-dark'        => 'btn-link-dark',
      'link-light'       => 'btn-link-light',
  ];

  $classes = $variants[$type] ?? $variants['primary'];
@endphp

<a
  href="{{ $url }}"
  @if($isExternal) target="_blank" rel="noopener noreferrer" @endif
  {{ $attributes->merge(['class' => $classes]) }}
>{{ $slot }}</a>

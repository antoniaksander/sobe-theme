@props([
    'type'  => 'sale',   // sale | new | out-of-stock
    'label' => null,
])

@php
  $defaults = [
      'sale'         => ['label' => __('Sale', 'sobe'),         'classes' => 'bg-accent text-accent-fg'],
      'new'          => ['label' => __('New', 'sobe'),          'classes' => 'bg-primary text-primary-fg'],
      'out-of-stock' => ['label' => __('Sold Out', 'sobe'),     'classes' => 'bg-surface-invert text-surface-invert-fg'],
  ];

  $config     = $defaults[$type] ?? $defaults['sale'];
  $badgeLabel = $label ?? $config['label'];
  $badgeClass = $config['classes'];
@endphp

<span class="absolute top-3 left-3 z-10 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold tracking-wide {{ $badgeClass }}">
  {{ $badgeLabel }}
</span>

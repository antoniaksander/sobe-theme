@php
  $variant = $attributes['variant'] ?? 'header-1';
@endphp

@includeIf('sections.' . $variant)

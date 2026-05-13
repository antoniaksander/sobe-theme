@php
  $variant = $attributes['variant'] ?? 'layout-2';
@endphp

@includeIf('sections.footer-' . $variant)

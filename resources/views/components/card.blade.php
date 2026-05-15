@props([])

<div {{ $attributes->merge(['class' => 'group relative flex flex-col bg-surface-1 rounded-2xl overflow-hidden border border-border shadow-sm hover:shadow-md transition-shadow duration-300']) }}>

  @isset($image)
    <div class="aspect-[4/3] overflow-hidden bg-surface-2">
      {{ $image }}
    </div>
  @endisset

  <div class="flex flex-col flex-1 p-6">

    @isset($title)
      <div class="mb-2 font-semibold text-heading text-lg leading-snug">
        {{ $title }}
      </div>
    @endisset

    @isset($body)
      <div class="flex-1 text-text-muted text-sm leading-relaxed">
        {{ $body }}
      </div>
    @endisset

    @isset($footer)
      <div class="mt-4 pt-4 border-t border-border">
        {{ $footer }}
      </div>
    @endisset

    @if(! isset($title) && ! isset($body) && ! isset($footer))
      {{ $slot }}
    @endif

  </div>

</div>

@php
  $heading        = $attributes['heading']            ?? '';
  $paragraph      = $attributes['paragraph']          ?? '';
  $ctaText        = $attributes['ctaText']            ?? '';
  $ctaUrl         = $attributes['ctaUrl']             ?? '';
  $ctaType        = $attributes['ctaType']            ?? 'btn-light';
  $headingColor   = $attributes['headingColor']       ?? 'fg';
  $paragraphColor = $attributes['paragraphColor']     ?? 'fg-muted';
  $headingSize    = $attributes['headingSize']        ?? 'default';
  $alignment      = $attributes['alignment']          ?? 'left';
  $height         = $attributes['height']             ?? '80vh';
  $imageId        = $attributes['backgroundImageId']  ?? 0;
  $imageUrl       = $attributes['backgroundImageUrl'] ?? '';
  $darkOverlay    = $attributes['darkOverlay']        ?? false;
  $data = compact(
    'heading',
    'paragraph',
    'ctaText',
    'ctaUrl',
    'ctaType',
    'headingColor',
    'paragraphColor',
    'headingSize',
    'alignment',
    'height',
    'imageId',
    'imageUrl',
    'darkOverlay'
  );
  $data = apply_filters('sobe/hero/data', $data, $attributes);
  $data = is_array($data) ? $data : [];
  extract($data, EXTR_OVERWRITE);
  $view = apply_filters('sobe/hero/view', '', $data, $attributes);
  $backgroundView = apply_filters('sobe/hero/background_view', '', $data, $attributes);
  $ctaView = apply_filters('sobe/hero/cta_view', '', $data, $attributes);

  // Full static class names — no string concatenation (Tailwind Static Class Rule / AP-3).
  $heightClass = match($height) {
    '70vh'  => 'min-h-[70vh]',
    '90vh'  => 'min-h-[90vh]',
    '100vh' => 'min-h-screen',
    default => 'min-h-[80vh]',
  };

  $alignmentClass = match($alignment) {
    'center'       => 'hero--center',
    'split-screen' => 'hero--split',
    'editorial'    => 'hero--editorial',
    default        => 'hero--left',
  };

  // Content wrapper — full static strings per variant to avoid w-full / w-1/2 conflict.
  // max-w-standard (80rem = --layout-content) constrains text to the same grid as the
  // page content below the hero so left/right edges align at every viewport width.
  $contentWrapClasses = match($alignment) {
    'center'       => 'relative z-10 w-full max-w-standard mx-auto px-6 lg:px-16 py-20 lg:py-32 flex flex-col items-center text-center',
    'split-screen' => 'relative z-10 w-1/2 pl-6 lg:pl-16 pr-8 py-20 lg:py-32 flex flex-col items-start text-left',
    'editorial'    => 'relative z-10 w-full max-w-standard mx-auto px-6 lg:px-16 py-20 lg:py-32 self-stretch flex flex-col items-start justify-between',
    default        => 'relative z-10 w-full max-w-standard mx-auto px-6 lg:px-16 py-20 lg:py-32 flex flex-col items-start text-left',
  };

  // Heading colour — token-mapped static class names, never string concatenation.
  $headingTextClass = match($headingColor) {
    'heading' => 'text-heading',
    'text'    => 'text-text',
    'accent'  => 'text-accent',
    default   => 'text-primary-fg',
  };

  // Paragraph colour — token-mapped static class names.
  $paraTextClass = match($paragraphColor) {
    'text'         => 'text-text',
    'text-muted'   => 'text-text-muted',
    'text-subtle'  => 'text-text-subtle',
    default        => 'text-primary-fg/80',
  };

  // Heading size — non-editorial only (editorial uses display clamp in style.scss).
  $headingSizeClass = match($headingSize) {
    'lg'  => 'text-5xl md:text-6xl lg:text-7xl',
    'xl'  => 'text-6xl md:text-7xl lg:text-8xl',
    default => 'text-4xl md:text-5xl lg:text-6xl',
  };

  // Editorial variant: body + CTA anchor to bottom-right.
  $editorialBodyClass = 'self-end text-right max-w-md flex flex-col gap-6';

  $imageAlt = $imageId
    ? (string) (get_post_meta($imageId, '_wp_attachment_image_alt', true) ?: '')
    : '';

  $wrapperAttrs = get_block_wrapper_attributes([
    'class' => "hero hero--sobe {$alignmentClass} {$heightClass} relative overflow-hidden flex",
  ]);
@endphp

@if($view)
  @include($view, ['data' => $data, 'attributes' => $attributes])
@else
<section
  {!! $wrapperAttrs !!}
  aria-label="{{ esc_attr(wp_strip_all_tags($heading)) }}"
  data-animate="fade-up"
>

  {{-- ── Background image layer ────────────────────────────────────────── --}}
  @if($backgroundView)
    @include($backgroundView, ['data' => $data, 'attributes' => $attributes])
  @elseif($imageUrl)
    <figure class="hero__bg-media absolute inset-0 m-0 pointer-events-none" aria-hidden="true">
      <img
        src="{{ esc_url($imageUrl) }}"
        alt="{{ esc_attr($imageAlt) }}"
        class="w-full h-full object-cover"
        loading="eager"
        fetchpriority="high"
        decoding="async"
      />
      @if($darkOverlay)
        <div class="absolute inset-0 bg-black/50" aria-hidden="true"></div>
      @endif
    </figure>
  @elseif($darkOverlay)
    <div class="absolute inset-0 bg-black/50 pointer-events-none" aria-hidden="true"></div>
  @endif

  {{-- ── Content layer ───────────────────────────────────────────────────── --}}
  <div class="{{ $contentWrapClasses }}" data-animate="hero-content">

    @if($alignment === 'editorial')

      {{-- Headline spans the full width at display scale (size set in style.scss) --}}
      @if($heading)
        <h1 class="hero__heading font-heading font-black {{ $headingTextClass }}">
          {!! wp_kses_post($heading) !!}
        </h1>
      @endif

      {{-- Description + CTA anchor to bottom-right --}}
      <div class="{{ $editorialBodyClass }}">
        @if($paragraph)
          <p class="text-lg leading-relaxed {{ $paraTextClass }}">
            {!! wp_kses_post($paragraph) !!}
          </p>
        @endif
        @if($ctaText && $ctaUrl)
          <div>
            @if($ctaView)
              @include($ctaView, ['data' => $data, 'attributes' => $attributes])
            @else
              <x-button :url="esc_url($ctaUrl)" :type="$ctaType">
                {!! wp_kses_post($ctaText) !!}
              </x-button>
            @endif
          </div>
        @endif
      </div>

    @else

      {{-- Left / Center / Split-Screen --}}
      <div class="flex flex-col gap-6">
        @if($heading)
          <h1 class="hero__heading {{ $headingSizeClass }} font-heading font-bold leading-tight tracking-tight {{ $headingTextClass }}">
            {!! wp_kses_post($heading) !!}
          </h1>
        @endif
        @if($paragraph)
          <p class="text-lg md:text-xl leading-relaxed max-w-prose {{ $paraTextClass }}">
            {!! wp_kses_post($paragraph) !!}
          </p>
        @endif
        @if($ctaText && $ctaUrl)
          <div>
            @if($ctaView)
              @include($ctaView, ['data' => $data, 'attributes' => $attributes])
            @else
              <x-button :url="esc_url($ctaUrl)" :type="$ctaType">
                {!! wp_kses_post($ctaText) !!}
              </x-button>
            @endif
          </div>
        @endif
      </div>

    @endif

  </div>

</section>
@endif

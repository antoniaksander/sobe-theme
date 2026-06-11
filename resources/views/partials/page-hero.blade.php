@php
  $postId = $heroPostId ?? get_the_ID();

  $resolvedHeroImage = isset($heroImage) && is_string($heroImage) && trim($heroImage) !== ''
    ? trim($heroImage)
    : get_the_post_thumbnail_url($postId, 'full');

  // Decode entities so the title/paragraph render once via {{ }}. get_the_title()
  // returns HTML-encoded text (& -> &#038;); decoding is a no-op on the already
  // raw term name / plain-text meta, so this is safe for every source.
  $resolvedHeroTitle = html_entity_decode(
    isset($heroTitle) ? (string) $heroTitle : (string) get_the_title($postId),
    ENT_QUOTES,
    'UTF-8'
  );

  $resolvedHeroParagraph = html_entity_decode(
    isset($heroParagraph) ? (string) $heroParagraph : (string) get_post_meta($postId, '_sobe_page_hero_text', true),
    ENT_QUOTES,
    'UTF-8'
  );

  $heroClasses = ['sobe-page-hero'];
  if (! empty($heroModifier)) {
    $heroClasses[] = sanitize_html_class($heroModifier);
  }

  $showTitle = empty($hideTitle);
  $hasParagraph = trim($resolvedHeroParagraph) !== '';
@endphp
<div
  class="{{ implode(' ', $heroClasses) }}"
  style="--_hero-bg: url('{!! esc_url($resolvedHeroImage) !!}')"
>
  <div class="sobe-page-hero__overlay" aria-hidden="true"></div>
  @if($showTitle || $hasParagraph)
    <div class="sobe-page-hero__inner">
      @if($showTitle)
        <h1 class="sobe-page-hero__title">{{ $resolvedHeroTitle }}</h1>
      @endif
      @if($hasParagraph)
        <p class="sobe-page-hero__subtitle">{{ $resolvedHeroParagraph }}</p>
      @endif
    </div>
  @endif
</div>

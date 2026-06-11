@php
  $postId     = $heroPostId ?? get_the_ID();
  $heroImage  = get_the_post_thumbnail_url($postId, 'full');
  $heroTitle  = get_the_title($postId);
  $showTitle  = empty($hideTitle);
@endphp
<div
  class="sobe-page-hero"
  style="--_hero-bg: url('{!! esc_url($heroImage) !!}')"
  role="banner"
>
  <div class="sobe-page-hero__overlay" aria-hidden="true"></div>
  @if($showTitle)
    <div class="sobe-page-hero__inner">
      <h1 class="sobe-page-hero__title">{!! $heroTitle !!}</h1>
    </div>
  @endif
</div>

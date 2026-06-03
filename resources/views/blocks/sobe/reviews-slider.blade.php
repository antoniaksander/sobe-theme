@php
  /** @var array $attributes */
  $dataMode      = $attributes['dataMode']      ?? 'auto';
  $productIds    = array_map('intval', (array) ($attributes['productIds']    ?? []));
  $wcReviewCount = (int) ($attributes['wcReviewCount'] ?? 8);
  $autoplayDelay = (int) ($attributes['autoplayDelay'] ?? 5000);
  $heading       = $attributes['heading']   ?? '';
  $paragraph     = $attributes['paragraph'] ?? '';
  $headerAlignment = $attributes['headerAlignment'] ?? 'center';
  $manualReviews = $attributes['reviews']   ?? [];

  // ── Build slides ─────────────────────────────────────────────────────────
  $slides = [];

  if ($dataMode === 'auto' && function_exists('wc_get_product')) {
    // Latest approved reviews across all products.
    $comments = get_comments([
      'status'  => 'approve',
      'type'    => 'review',
      'number'  => $wcReviewCount,
      'orderby' => 'comment_date',
      'order'   => 'DESC',
    ]);
    foreach ($comments as $comment) {
      $pid     = (int) $comment->comment_post_ID;
      $product = wc_get_product($pid);
      $slides[] = [
        'rating'       => max(1, min(5, (int) get_comment_meta($comment->comment_ID, 'rating', true) ?: 5)),
        'text'         => wp_strip_all_tags($comment->comment_content),
        'author'       => $comment->comment_author,
        'productTitle' => $product ? $product->get_name() : '',
        'productUrl'   => $product ? get_permalink($pid) : '',
        'imageUrl'     => $product ? (wp_get_attachment_image_url($product->get_image_id(), 'large') ?: '') : '',
        'imageAlt'     => $product ? $product->get_name() : '',
      ];
    }

  } elseif ($dataMode === 'products' && !empty($productIds) && function_exists('wc_get_product')) {
    $comments = get_comments([
      'post__in' => $productIds,
      'status'   => 'approve',
      'type'     => 'review',
      'number'   => $wcReviewCount * count($productIds),
      'orderby'  => 'comment_date',
      'order'    => 'DESC',
    ]);
    foreach ($comments as $comment) {
      $pid     = (int) $comment->comment_post_ID;
      $product = wc_get_product($pid);
      $slides[] = [
        'rating'       => max(1, min(5, (int) get_comment_meta($comment->comment_ID, 'rating', true) ?: 5)),
        'text'         => wp_strip_all_tags($comment->comment_content),
        'author'       => $comment->comment_author,
        'productTitle' => $product ? $product->get_name() : '',
        'productUrl'   => $product ? get_permalink($pid) : '',
        'imageUrl'     => $product ? (wp_get_attachment_image_url($product->get_image_id(), 'large') ?: '') : '',
        'imageAlt'     => $product ? $product->get_name() : '',
      ];
    }

  } else {
    // Manual entries.
    foreach ($manualReviews as $r) {
      $slides[] = [
        'rating'       => max(1, min(5, (int) ($r['rating'] ?? 5))),
        'text'         => wp_strip_all_tags($r['text'] ?? ''),
        'author'       => sanitize_text_field($r['author'] ?? ''),
        'productTitle' => sanitize_text_field($r['productTitle'] ?? ''),
        'productUrl'   => esc_url($r['productUrl'] ?? ''),
        'imageUrl'     => esc_url($r['imageUrl'] ?? ''),
        'imageAlt'     => sanitize_text_field($r['imageAlt'] ?? ''),
      ];
    }
  }

  // Filter out slides with no review text.
  $slides = array_filter($slides, fn($s) => $s['text'] !== '');
  $slides = array_values($slides);
  $slides = apply_filters('sobe/reviews_slider/slides', $slides, $attributes);

  if (empty($slides)) {
    return;
  }

  $wrapperAttrs = get_block_wrapper_attributes([
    'class'               => 'reviews-slider reviews-slider--sobe',
    'data-autoplay-delay' => (string) $autoplayDelay,
  ]);
  $view = apply_filters('sobe/reviews_slider/view', '', $slides, $attributes);
@endphp

@if($view)
  @include($view, ['slides' => $slides, 'attributes' => $attributes])
@else
<section {!! $wrapperAttrs !!} data-animate="fade-up">

  {{-- ── Section header ──────────────────────────────────────────────────── --}}
  @if($heading || $paragraph)
    <div class="reviews-slider__header reviews-slider__header--{{ esc_attr($headerAlignment) }} mb-8">
      @if($heading)
        <h2 class="text-2xl md:text-3xl font-bold text-heading m-0 leading-tight">
          {{ esc_html($heading) }}
        </h2>
      @endif
      @if($paragraph)
        <p class="text-text-muted mt-2 mb-0">{{ esc_html($paragraph) }}</p>
      @endif
    </div>
  @endif

  <div class="reviews-slider__stage">
    <div class="reviews-slider__content-stage" aria-live="polite">
      @foreach($slides as $i => $slide)
        <article
          class="reviews-slider__content{{ $i === 0 ? ' is-active' : '' }}"
          data-review-content
          data-index="{{ $i }}"
          aria-hidden="{{ $i === 0 ? 'false' : 'true' }}"
        >
          <div class="reviews-slider__copy">
            <div class="reviews-slider__stars" aria-label="{{ sprintf(__('%d out of 5 stars', 'sobe'), $slide['rating']) }}">
              @for($s = 1; $s <= 5; $s++)
                <svg width="18" height="18" viewBox="0 0 24 24"
                  fill="{{ $s <= $slide['rating'] ? 'var(--c-stars)' : 'none' }}"
                  stroke="{{ $s <= $slide['rating'] ? 'var(--c-stars)' : 'var(--c-text-subtle)' }}"
                  stroke-width="1.5" aria-hidden="true">
                  <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
              @endfor
            </div>

            <blockquote class="reviews-slider__quote">
              {{ esc_html($slide['text']) }}
            </blockquote>
            <p class="reviews-slider__author">— {{ esc_html($slide['author']) }}</p>
            @if(count($slides) > 1)
              <div class="reviews-slider__nav">
                <button type="button" class="reviews-slider__btn reviews-slider__btn--prev" aria-label="{{ __('Previous review', 'sobe') }}">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
                <button type="button" class="reviews-slider__btn reviews-slider__btn--next" aria-label="{{ __('Next review', 'sobe') }}">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
              </div>
            @endif
          </div>
        </article>
      @endforeach
    </div>

    <div class="reviews-slider__media-stage">
      @foreach($slides as $i => $slide)
        @if($slide['productUrl'])
          <a
            href="{!! esc_url($slide['productUrl']) !!}"
            class="reviews-slider__image-wrap reviews-slider__image-link{{ $i === 0 ? ' is-active' : '' }}"
            data-review-image
            data-index="{{ $i }}"
            aria-hidden="{{ $i === 0 ? 'false' : 'true' }}"
          >
        @else
          <div
          class="reviews-slider__image-wrap{{ $i === 0 ? ' is-active' : '' }}"
          data-review-image
          data-index="{{ $i }}"
          aria-hidden="{{ $i === 0 ? 'false' : 'true' }}"
          >
        @endif
          @if($slide['imageUrl'])
            <img src="{{ $slide['imageUrl'] }}" alt="{{ esc_attr($slide['imageAlt'] ?: $slide['productTitle']) }}" loading="lazy" />
          @else
            <div class="reviews-slider__image-placeholder">
              <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true">
                <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/>
              </svg>
            </div>
          @endif

          @if($slide['productTitle'] || $slide['productUrl'])
            <div class="reviews-slider__product-info">
              @if($slide['productTitle'])
                <p class="reviews-slider__product-name">{{ esc_html($slide['productTitle']) }}</p>
              @endif
              @if($slide['productUrl'])
                <span class="reviews-slider__shop-link">
                  {{ __('Shop now', 'sobe') }}
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
                </span>
              @endif
            </div>
          @endif
        @if($slide['productUrl'])
          </a>
        @else
          </div>
        @endif
      @endforeach
    </div>
  </div>

  @if(count($slides) > 1)
    <div class="reviews-slider__dots" role="tablist" aria-label="{{ __('Review slides', 'sobe') }}">
      @foreach($slides as $i => $slide)
        <button
          type="button"
          class="reviews-slider__dot{{ $i === 0 ? ' is-active' : '' }}"
          data-index="{{ $i }}"
          role="tab"
          aria-label="{{ sprintf(__('Slide %d', 'sobe'), $i + 1) }}"
          aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
        ></button>
      @endforeach
    </div>
  @endif

</section>
@endif

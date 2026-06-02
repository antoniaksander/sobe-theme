<li class="product search-result-card search-result-card--post">
  @if(has_post_thumbnail())
    <a href="{{ get_permalink() }}" class="search-result-card__image-link" tabindex="-1" aria-hidden="true">
      {!! get_the_post_thumbnail(null, 'woocommerce_thumbnail', ['class' => 'search-result-card__thumb']) !!}
    </a>
  @else
    <a href="{{ get_permalink() }}" class="search-result-card__image-link search-result-card__image-link--empty" tabindex="-1" aria-hidden="true"></a>
  @endif
  <div class="search-result-card__body">
    <span class="search-result-card__type">{{ __('Post', 'sobe') }}</span>
    <h2 class="search-result-card__title"><a href="{{ get_permalink() }}">{!! get_the_title() !!}</a></h2>
    <p class="search-result-card__excerpt">{!! wp_trim_words(get_the_excerpt(), 20) !!}</p>
    <time class="search-result-card__date" datetime="{{ get_the_date('c') }}">{{ get_the_date() }}</time>
  </div>
</li>

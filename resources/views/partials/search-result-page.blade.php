<article class="search-result-card search-result-card--page">
  <div class="search-result-card__body">
    <span class="search-result-card__type">{{ __('Page', 'sobe') }}</span>
    <h2 class="search-result-card__title"><a href="{{ get_permalink() }}">{!! get_the_title() !!}</a></h2>
    <p class="search-result-card__excerpt">{!! wp_trim_words(get_the_excerpt(), 20) !!}</p>
  </div>
</article>

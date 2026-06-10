@extends('layouts.app')

@section('content')
  @php
    $heroPostId = (int) get_option('page_for_posts') ?: null;
    $showHero   = $heroPostId && is_home() && !is_front_page()
                    && get_post_meta($heroPostId, '_sobe_page_hero', true)
                    && has_post_thumbnail($heroPostId);
    $hideTitle  = $heroPostId && is_home() && !is_front_page()
                    && (bool) get_post_meta($heroPostId, '_sobe_hide_title', true);
  @endphp

  @if($showHero)
    @include('partials.page-hero')
  @endif

  <x-section width="standard" :padding="$showHero ? 'hero' : 'default'">
    @if(!$showHero && !$hideTitle)
      @include('partials.page-header')
    @endif

    @if(! have_posts())
      <x-alert type="warning">{!! __('Sorry, no results were found.', 'sobe') !!}</x-alert>
      {!! get_search_form(false) !!}
    @else
      <div class="sobe-post-list">
        @while(have_posts()) @php
          the_post();
          $cats     = get_the_category();
          $ctaLabel = get_post_meta(get_the_ID(), '_sobe_post_cta', true) ?: __('Read article', 'sobe');
        @endphp
          <article class="sobe-post-row {{ has_post_thumbnail() ? 'sobe-post-row--has-image' : '' }}">
            @if(has_post_thumbnail())
              <a class="sobe-post-row__media" href="{{ get_permalink() }}" tabindex="-1" aria-hidden="true">
                {!! get_the_post_thumbnail(get_the_ID(), 'large', ['class' => 'sobe-post-row__img']) !!}
              </a>
            @endif
            <div class="sobe-post-row__body">
              @if(!empty($cats))
                <span class="sobe-post-row__category">{!! esc_html($cats[0]->name) !!}</span>
              @endif
              <h2 class="sobe-post-row__title">
                <a href="{{ get_permalink() }}">{!! get_the_title() !!}</a>
              </h2>
              <p class="sobe-post-row__excerpt">{!! get_the_excerpt() !!}</p>
              <a class="sobe-post-row__cta" href="{{ get_permalink() }}">{{ $ctaLabel }}</a>
            </div>
          </article>
        @endwhile
      </div>

      {!! get_the_posts_pagination([
        'class'     => 'sobe-wp-pagination',
        'prev_text' => __('← Previous', 'sobe'),
        'next_text' => __('Next →', 'sobe'),
      ]) !!}
    @endif
  </x-section>
@endsection

@section('sidebar')
  @include('sections.sidebar')
@endsection

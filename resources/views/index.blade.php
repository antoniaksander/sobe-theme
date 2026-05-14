@extends('layouts.app')

@section('content')
  @if (have_posts())
    @while (have_posts()) @php(the_post())
      <article @php(post_class('wp-entry'))>
        <h2>
          <a href="{{ esc_url(get_permalink()) }}">
            {!! wp_kses_post(get_the_title()) !!}
          </a>
        </h2>

        <div class="wp-entry__content">
          {!! get_the_excerpt() !!}
        </div>
      </article>
    @endwhile

    {!! get_the_posts_navigation() !!}
  @else
    <p>{{ __('No posts found.', config('theme.textdomain')) }}</p>
  @endif
@endsection

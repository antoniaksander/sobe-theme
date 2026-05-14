@extends('layouts.app')

@section('content')
  @while (have_posts()) @php(the_post())
    <article @php(post_class('wp-page'))>
      <h1>{!! wp_kses_post(get_the_title()) !!}</h1>
      {!! the_content() !!}
    </article>
  @endwhile
@endsection

@extends('layouts.app')

@section('content')
  <x-section width="standard" padding="default">
      @while(have_posts()) @php the_post(); @endphp
      @includeFirst(['partials.content-single-' . get_post_type(), 'partials.content-single'])
    @endwhile
  </x-section>
@endsection

@extends('layouts.app')

@section('content')
  @while(have_posts()) @php the_post(); @endphp
<div class="is-layout-constrained max-w-none">
    @php the_content(); @endphp
</div>
  @endwhile
@endsection
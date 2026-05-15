<div class="is-layout-constrained max-w-none">
  @php the_content(); @endphp
</div>

@if ($pagination())
  <nav class="page-nav" aria-label="Page">
    {!! $pagination !!}
  </nav>
@endif

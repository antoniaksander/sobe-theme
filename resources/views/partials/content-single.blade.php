<article @php post_class('h-entry'); @endphp>
  <header>
    <h1 class="p-name">
      {!! $title !!}
    </h1>

    @include('partials.entry-meta')
  </header>

  <div class="e-content is-layout-constrained max-w-none">
    @php the_content(); @endphp
  </div>

  @if ($pagination())
    <footer>
      <nav class="page-nav" aria-label="Page">
        {!! $pagination !!}
      </nav>
    </footer>
  @endif

  @php comments_template(); @endphp
</article>

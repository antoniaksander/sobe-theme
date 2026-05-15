<footer class="bg-background border-t border-border mt-auto">
  <div class="container max-w-standard py-section">
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-12 lg:gap-24">

      {{-- Brand block --}}
      <div class="shrink-0 max-w-xs">
        <a href="{{ home_url('/') }}" class="inline-block font-heading font-bold text-2xl text-heading no-underline leading-none">
          {{ get_bloginfo('name') }}
        </a>
        @if (get_bloginfo('description'))
          <p class="mt-3 text-sm text-text-muted leading-relaxed">{{ get_bloginfo('description') }}</p>
        @endif
        <p class="mt-8 text-xs text-text-subtle">&copy; {{ date('Y') }} {{ get_bloginfo('name') }}</p>
      </div>

      {{-- Widget area --}}
      @if (is_active_sidebar('sidebar-footer'))
        <div class="flex flex-wrap gap-x-16 gap-y-10
                    [&_h3]:text-xs [&_h3]:font-semibold [&_h3]:tracking-wider [&_h3]:uppercase [&_h3]:text-text-muted [&_h3]:mb-4
                    [&_ul]:list-none [&_ul]:m-0 [&_ul]:p-0 [&_ul]:flex [&_ul]:flex-col [&_ul]:gap-2
                    [&_a]:text-sm [&_a]:text-text-muted [&_a:hover]:text-text [&_a]:no-underline [&_a]:transition-colors [&_a]:duration-150">
          @php dynamic_sidebar('sidebar-footer'); @endphp
        </div>
      @endif

    </div>
  </div>
</footer>

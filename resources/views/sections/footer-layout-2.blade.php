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

      @if (has_nav_menu('footer_navigation'))
        <nav
          class="flex flex-col gap-2 text-sm text-text-muted [&_ul]:list-none [&_ul]:m-0 [&_ul]:p-0 [&_ul]:flex [&_ul]:flex-col [&_ul]:gap-2 [&_a]:text-text-muted [&_a:hover]:text-text [&_a]:no-underline [&_a]:transition-colors [&_a]:duration-150"
          aria-label="{{ \App\sobe_navigation_label('footer_navigation', __('Footer navigation', 'sobe')) }}"
        >
          <h2 class="text-xs font-semibold tracking-wider uppercase text-text-muted mb-2">{{ __('Navigate', 'sobe') }}</h2>
          {!! wp_nav_menu([
            'theme_location' => 'footer_navigation',
            'menu_class'     => 'flex flex-col gap-2 text-sm list-none',
            'container'      => false,
            'echo'           => false,
            'depth'          => 1,
          ]) !!}
        </nav>
      @elseif (! is_active_sidebar('sidebar-footer'))
        <nav class="flex flex-col gap-2 text-sm" aria-label="{{ __('Footer navigation', 'sobe') }}">
          <h2 class="text-xs font-semibold tracking-wider uppercase text-text-muted mb-2">{{ __('Navigate', 'sobe') }}</h2>
          <ul class="flex flex-col gap-2 list-none m-0 p-0">
            @foreach (\App\sobe_footer_fallback_links() as $link)
              @if (! empty($link['url']) && ! empty($link['label']))
                <li>
                  <a class="text-text-muted hover:text-text no-underline transition-colors duration-150" href="{{ esc_url($link['url']) }}">
                    {{ $link['label'] }}
                  </a>
                </li>
              @endif
            @endforeach
          </ul>
        </nav>
      @endif

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

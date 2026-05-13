@if(get_theme_mod(config('theme.prefix').'_header_wishlist', false) && class_exists('YITH_WCWL'))
  <a href="{{ YITH_WCWL()->get_wishlist_url() }}"
     aria-label="{{ __('Wishlist', 'sobe') }}"
     class="flex items-center justify-center w-10 h-10 rounded-lg text-text hover:bg-surface-2 transition-colors duration-200">
    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24"
         stroke="currentColor" stroke-width="1.5" aria-hidden="true">
      <path stroke-linecap="round" stroke-linejoin="round"
            d="M4.318 6.318a4.5 4.5 0 016.364 0L12 7.636l1.318-1.318a4.5 4.5 0 116.364 6.364L12 20.364l-7.682-7.682a4.5 4.5 0 010-6.364z"/>
    </svg>
  </a>
@endif

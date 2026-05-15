<button
  data-dark-toggle
  @click="toggleDark()"
  class="p-2 rounded-lg bg-surface-2 text-text hover:bg-surface-3 transition-colors duration-200"
  :aria-label="dark ? '{{ __('Switch to light mode', 'sobe') }}' : '{{ __('Switch to dark mode', 'sobe') }}'"
  :title="dark ? '{{ __('Switch to light mode', 'sobe') }}' : '{{ __('Switch to dark mode', 'sobe') }}'"
>
  {{-- Sun: visible in dark mode → click to go light --}}
  <template x-if="dark">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-10h-1M4 12H3m15.364-6.364-.707.707M6.343 17.657l-.707.707m12.728 0-.707-.707M6.343 6.343l-.707-.707M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8z" />
    </svg>
  </template>

  {{-- Moon: visible in light mode → click to go dark --}}
  <template x-if="!dark">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
      <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 0 1 8.646 3.646 9.004 9.004 0 0 0 12 21a9.004 9.004 0 0 0 8.354-5.646z" />
    </svg>
  </template>
</button>

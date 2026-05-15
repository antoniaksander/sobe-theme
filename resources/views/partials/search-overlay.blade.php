<div
  x-data="searchOverlay"
  x-trap.inert="open"
  x-show="open"
  x-transition:enter="transition-opacity"
  x-transition:enter-start="opacity-0"
  x-transition:enter-end="opacity-100"
  x-transition:leave="transition-opacity"
  x-transition:leave-start="opacity-100"
  x-transition:leave-end="opacity-0"
  @open-search.window="openOverlay()"
  @keydown.escape.window="close()"
  class="sobe-overlay"
  role="dialog"
  aria-modal="true"
  aria-label="{{ __('Search', 'sobe') }}"
  style="display:none"
>
  <div class="sobe-overlay__backdrop" @click="close()" aria-hidden="true"></div>

  <div class="sobe-overlay__panel">
    <div class="sobe-overlay__input-row">
      <input
        x-ref="searchInput"
        type="search"
        x-model="query"
        @keydown="handleKey($event)"
        class="sobe-overlay__input"
        placeholder="{{ __('Search…', 'sobe') }}"
        aria-label="{{ __('Search', 'sobe') }}"
        autocomplete="off"
      >
      <button
        x-show="query.length > 0"
        @click="query = ''; results = []; $refs.searchInput.focus()"
        class="sobe-overlay__clear"
        type="button"
        aria-label="{{ __('Clear search', 'sobe') }}"
      >{{ __('Clear', 'sobe') }}</button>
      <button
        @click="close()"
        class="sobe-overlay__close"
        aria-label="{{ __('Close search', 'sobe') }}"
      >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <ul
      x-show="results.length > 0"
      class="sobe-overlay__results"
      role="listbox"
      aria-live="polite"
      aria-label="{{ __('Search results', 'sobe') }}"
    >
      <template x-for="(result, i) in results" :key="result.id">
        <li
          role="option"
          :aria-selected="activeIndex === i"
          :class="{ 'is-active': activeIndex === i }"
          class="sobe-overlay__result"
        >
          <a :href="result.url">
            <img
              x-show="result.thumbnail"
              :src="result.thumbnail"
              :alt="result.title"
              class="sobe-overlay__result-thumb"
              loading="lazy"
              width="48"
              height="48"
            >
            <div class="sobe-overlay__result-meta">
              <span class="sobe-overlay__result-title" x-text="result.title"></span>
              <span x-show="result.price_html" class="sobe-overlay__result-price" x-html="result.price_html"></span>
            </div>
          </a>
        </li>
      </template>
    </ul>

    <p
      x-show="query.length >= 2 && results.length === 0 && !loading"
      class="sobe-overlay__empty"
    >
      {{ __('No results found.', 'sobe') }}
    </p>

    <a
      x-show="results.length > 0"
      :href="`${searchPageUrl}?s=${encodeURIComponent(query)}`"
      class="sobe-overlay__view-all"
    >
      {{ __('View all results', 'sobe') }}
    </a>
  </div>
</div>

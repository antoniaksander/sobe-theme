@php
  $messages = \App\sobe_announcement_bar_messages();
  if (empty($messages)) return;
  $isMultiple   = count($messages) > 1;
  $jsonMessages = wp_json_encode($messages, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
@endphp

<div
  class="announcement-bar"
  x-data="{
    messages: {{ $jsonMessages }},
    multiple: {{ $isMultiple ? 'true' : 'false' }},
    current: 0,
    transitioning: false,
    visible: true,
    atTop: true,
    _ready: false,
    _barH: 0,
    _timer: null,
    _scrollFn: null,
    mount() {
      if (!this.multiple && sessionStorage.getItem('sobe-bar-dismissed') === '1') {
        this.visible = false;
        return;
      }
      this.atTop = window.scrollY < 2;
      this.$nextTick(() => {
        this._barH = this.$el.offsetHeight;
        document.documentElement.style.setProperty('--bar-h', this.atTop ? this._barH + 'px' : '0px');
        this._ready = true;
      });
      this._scrollFn = () => {
        const atTop = window.scrollY < 2;
        if (this.atTop === atTop) return;
        this.atTop = atTop;
        document.documentElement.style.setProperty('--bar-h', atTop ? this._barH + 'px' : '0px');
      };
      window.addEventListener('scroll', this._scrollFn, { passive: true });
      if (this.multiple) {
        this._timer = setInterval(() => this.advance(), 4000);
      }
    },
    advance() {
      this.transitioning = true;
      setTimeout(() => {
        this.current = (this.current + 1) % this.messages.length;
        this.transitioning = false;
      }, 350);
    },
    dismiss() {
      document.documentElement.style.setProperty('--bar-h', '0px');
      window.removeEventListener('scroll', this._scrollFn);
      sessionStorage.setItem('sobe-bar-dismissed', '1');
      this.visible = false;
      clearInterval(this._timer);
    },
  }"
  x-init="mount()"
  x-show="visible"
  x-cloak
  :class="{ 'announcement-bar--scrolled': !atTop, 'announcement-bar--ready': _ready }"
  role="region"
  aria-label="{{ __('Announcement', 'sobe') }}"
>
  <div class="announcement-bar__inner">
    <span
      class="announcement-bar__text"
      x-text="messages[current]"
      :class="{ 'opacity-0': transitioning }"
    ></span>
    @if (! $isMultiple)
      <button
        class="announcement-bar__close"
        @click="dismiss()"
        aria-label="{{ __('Dismiss announcement', 'sobe') }}"
      >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
      </button>
    @endif
  </div>
</div>

@php
  $messages = \App\sobe_announcement_bar_messages();
  if (empty($messages)) return;
  $isMultiple   = count($messages) > 1;
  $jsonMessages = wp_json_encode($messages, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
@endphp

<div
  class="announcement-bar"
  data-announcement-bar
  role="region"
  aria-label="{{ __('Announcement', 'sobe') }}"
>
  <script type="application/json" data-announcement-bar-messages>{!! $jsonMessages !!}</script>
  <div class="announcement-bar__inner">
    <span
      class="announcement-bar__text"
      data-announcement-bar-text
    >{{ $messages[0] }}</span>
    @if (! $isMultiple)
      <button
        class="announcement-bar__close"
        type="button"
        data-announcement-bar-dismiss
        aria-label="{{ __('Dismiss announcement', 'sobe') }}"
      >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
      </button>
    @endif
  </div>
</div>

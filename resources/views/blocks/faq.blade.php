@php
  $faqs = $attributes['faqs'] ?? [];
  $wrapperAttrs = get_block_wrapper_attributes(['class' => 'sobe-faq']);
@endphp

<section {!! $wrapperAttrs !!} data-animate="fade-up">
  <div class="faq__header">
<!-- 1
    <svg class="faq__icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M9 4H7C5.89543 4 5 4.89543 5 6V20C5 21.1046 5.89543 22 7 22H17C18.1046 22 19 21.1046 19 20V6C19 4.89543 18.1046 4 17 4H15" stroke="currentColor" stroke-width="1.5"/>
      <path d="M12 2H14C15.1046 2 16 2.89543 16 4V6H8V4C8 2.89543 8.89543 2 10 2Z" stroke="currentColor" stroke-width="1.5"/>
      <path d="M9 11H15M9 15H13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    </svg>
-->
    <h2 class="faq__title">{{ __('Frequently Asked Questions', 'sobe') }}</h2>
  </div>

  <div class="faq__items">
    @forelse($faqs as $index => $faq)
      <div class="sobe-faq__item faq__item">
        <button
          class="sobe-faq__question-btn faq__question-wrapper"
          type="button"
          aria-expanded="false"
          aria-controls="faq-answer-{{ $index }}"
        >
          <span class="faq__question-text">{!! wp_kses_post($faq['question']) !!}</span>
          <svg class="faq__chevron" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
        <div
          id="faq-answer-{{ $index }}"
          class="sobe-faq__answer-wrapper faq__answer-wrapper"
        >
          <div class="sobe-faq__answer-inner faq__answer-inner">
            <p class="faq__answer-text">{!! wp_kses_post($faq['answer']) !!}</p>
          </div>
        </div>
      </div>
    @empty
      <p class="faq__empty">{{ __('No FAQ items yet.', 'sobe') }}</p>
    @endforelse
  </div>
</section>
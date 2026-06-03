import { registerReinit } from '../../js/sobe-reinit.js';

const instances = new WeakMap();
const blockSelector = '.reviews-slider--sobe';

function getBlocks(root = document) {
  const blocks = [...(root.querySelectorAll?.(blockSelector) || [])];
  if (root.nodeType === Node.ELEMENT_NODE && root.matches(blockSelector)) {
    blocks.unshift(root);
  }
  return blocks;
}

function init(root = document) {
  getBlocks(root).forEach((block) => {
    if (instances.has(block)) return;

    const delay = parseInt(block.dataset.autoplayDelay ?? '5000', 10);
    const contents = Array.from(block.querySelectorAll('[data-review-content]'));
    const images = Array.from(block.querySelectorAll('[data-review-image]'));
    const prevBtns = Array.from(block.querySelectorAll('.reviews-slider__btn--prev'));
    const nextBtns = Array.from(block.querySelectorAll('.reviews-slider__btn--next'));
    const dots = Array.from(block.querySelectorAll('.reviews-slider__dot'));
    const total = Math.min(contents.length, images.length);
    if (total <= 1) return;

    const controller = new AbortController();
    const { signal } = controller;
    const state = {
      animationTimerId: null,
      controller,
      currentIndex: Math.max(0, contents.findIndex((item) => item.classList.contains('is-active'))),
      isAnimating: false,
      timerId: null,
    };

    const syncUi = (nextIndex) => {
      contents.forEach((item, index) => {
        const isActive = index === nextIndex;
        item.classList.toggle('is-active', isActive);
        item.setAttribute('aria-hidden', isActive ? 'false' : 'true');
      });

      images.forEach((item, index) => {
        const isActive = index === nextIndex;
        item.classList.toggle('is-active', isActive);
        item.setAttribute('aria-hidden', isActive ? 'false' : 'true');
      });

      dots.forEach((dot, index) => {
        const isActive = index === nextIndex;
        dot.classList.toggle('is-active', isActive);
        dot.setAttribute('aria-selected', isActive ? 'true' : 'false');
      });
    };

    const queueNext = () => {
      window.clearTimeout(state.timerId);
      state.timerId = window.setTimeout(() => goTo(state.currentIndex + 1), delay);
    };

    const goTo = (targetIndex) => {
      const nextIndex = (targetIndex + total) % total;
      if (nextIndex === state.currentIndex || state.isAnimating) return;

      state.isAnimating = true;
      contents[state.currentIndex]?.classList.remove('is-active');
      images[state.currentIndex]?.classList.remove('is-active');

      window.clearTimeout(state.animationTimerId);
      state.animationTimerId = window.setTimeout(() => {
        state.currentIndex = nextIndex;
        syncUi(state.currentIndex);
        state.isAnimating = false;
        queueNext();
      }, 220);
    };

    prevBtns.forEach((button) => {
      button.addEventListener('click', () => goTo(state.currentIndex - 1), { signal });
    });

    nextBtns.forEach((button) => {
      button.addEventListener('click', () => goTo(state.currentIndex + 1), { signal });
    });

    dots.forEach((dot) => {
      dot.addEventListener('click', () => {
        const index = parseInt(dot.dataset.index ?? '0', 10);
        goTo(index);
      }, { signal });
    });

    block.addEventListener('mouseenter', () => window.clearTimeout(state.timerId), { signal });
    block.addEventListener('mouseleave', queueNext, { signal });

    syncUi(state.currentIndex);
    queueNext();

    instances.set(block, state);
  });
}

function destroy() {
  document.querySelectorAll(blockSelector).forEach((block) => {
    const state = instances.get(block);
    if (!state) return;

    state.controller.abort();
    window.clearTimeout(state.timerId);
    window.clearTimeout(state.animationTimerId);
    instances.delete(block);
  });
}

registerReinit('reviews-slider', { init, destroy });

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => init(document));
} else {
  init(document);
}

export { init, destroy };

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.sobe-reviews-slider').forEach((block) => {
    const delay = parseInt(block.dataset.autoplayDelay ?? '5000', 10);
    const contents = Array.from(block.querySelectorAll('[data-review-content]'));
    const images = Array.from(block.querySelectorAll('[data-review-image]'));
    const prevBtns = Array.from(block.querySelectorAll('.reviews-slider__btn--prev'));
    const nextBtns = Array.from(block.querySelectorAll('.reviews-slider__btn--next'));
    const dots = Array.from(block.querySelectorAll('.reviews-slider__dot'));
    const total = Math.min(contents.length, images.length);
    if (total <= 1) return;

    let currentIndex = Math.max(0, contents.findIndex((item) => item.classList.contains('is-active')));
    let timerId = null;
    let isAnimating = false;

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
      window.clearTimeout(timerId);
      timerId = window.setTimeout(() => goTo(currentIndex + 1), delay);
    };

    const goTo = (targetIndex) => {
      const nextIndex = (targetIndex + total) % total;
      if (nextIndex === currentIndex || isAnimating) return;

      isAnimating = true;
      contents[currentIndex]?.classList.remove('is-active');
      images[currentIndex]?.classList.remove('is-active');

      window.setTimeout(() => {
        currentIndex = nextIndex;
        syncUi(currentIndex);
        isAnimating = false;
        queueNext();
      }, 220);
    };

    prevBtns.forEach((button) => {
      button.addEventListener('click', () => goTo(currentIndex - 1));
    });

    nextBtns.forEach((button) => {
      button.addEventListener('click', () => goTo(currentIndex + 1));
    });

    dots.forEach((dot) => {
      dot.addEventListener('click', () => {
        const index = parseInt(dot.dataset.index ?? '0', 10);
        goTo(index);
      });
    });

    block.addEventListener('mouseenter', () => window.clearTimeout(timerId));
    block.addEventListener('mouseleave', queueNext);

    syncUi(currentIndex);
    queueNext();
  });
});

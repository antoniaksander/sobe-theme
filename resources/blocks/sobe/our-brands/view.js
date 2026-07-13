import { registerReinit } from '../../js/sobe-reinit.js';

const instances = new WeakMap();
const blockSelector = '[data-block="our-brands"]';

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

    const controller = new AbortController();
    const { signal } = controller;
    const navLinks = block.querySelectorAll('.brands-alpha-nav__letter');
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const letter = entry.target.dataset.section;
          navLinks.forEach((link) => {
            link.classList.toggle('is-active', link.dataset.letter === letter);
          });
        }
      });
    }, {
      rootMargin: '-25% 0px -65% 0px',
      threshold: 0,
    });
    const state = { controller, observer };

    navLinks.forEach((link) => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const target = document.querySelector(link.getAttribute('href'));
        if (!target) return;

        const header = document.querySelector('.site-header');
        const offset = -(header?.offsetHeight ?? 0);

        if (window.lenis) {
          window.lenis.scrollTo(target, { offset });
          // app.js only runs Lenis's raf loop while the user is actively
          // wheeling/touching (it self-stops 250ms after input goes idle,
          // to save CPU) — a scrollTo() called while the ticker is idle
          // (e.g. clicking a letter right after page load, before any
          // scrolling) sets the animation target but nothing ever advances
          // it. One manual raf() tick kicks it moving; app.js's own
          // 'scroll' listener takes over driving the rest of the animation.
          window.lenis.raf(performance.now());
        } else {
          const top = target.getBoundingClientRect().top + window.scrollY + offset;
          window.scrollTo({ top, behavior: 'smooth' });
        }
      }, { signal });
    });

    block.querySelectorAll('.brands-section').forEach((section) => {
      observer.observe(section);
    });

    instances.set(block, state);
  });
}

function destroy() {
  document.querySelectorAll(blockSelector).forEach((block) => {
    const state = instances.get(block);
    if (!state) return;

    state.controller.abort();
    state.observer.disconnect();
    instances.delete(block);
  });
}

registerReinit('our-brands', { init, destroy });

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => init(document));
} else {
  init(document);
}

export { init, destroy };

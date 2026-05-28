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

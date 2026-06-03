import { registerReinit } from '../../js/sobe-reinit.js';

const instances = new WeakMap();
const blockSelector = '.faq--sobe';

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
    const state = { controller };

    const items = block.querySelectorAll('.faq__item');

    items.forEach((item) => {
      const button = item.querySelector('.sobe-faq__question-btn');
      if (!button) return;

      button.addEventListener('click', (e) => {
        e.preventDefault();
        const isOpen = item.classList.contains('is-open');

        items.forEach((el) => {
          el.classList.remove('is-open');
          el.querySelector('.sobe-faq__question-btn')?.setAttribute(
            'aria-expanded',
            'false',
          );
        });

        if (!isOpen) {
          item.classList.add('is-open');
          button.setAttribute('aria-expanded', 'true');
        }
      }, { signal });
    });

    instances.set(block, state);
  });
}

function destroy() {
  document.querySelectorAll(blockSelector).forEach((block) => {
    const state = instances.get(block);
    if (!state) return;

    state.controller.abort();
    instances.delete(block);
  });
}

registerReinit('faq', { init, destroy });

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => init(document));
} else {
  init(document);
}

export { init, destroy };

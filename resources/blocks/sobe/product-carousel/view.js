import Swiper from 'swiper';
import { Navigation } from 'swiper/modules';
import { registerReinit } from '../../js/sobe-reinit.js';

const instances = new WeakMap();
const blockSelector = '.product-carousel-swiper';

function getBlocks(root = document) {
  const blocks = [...(root.querySelectorAll?.(blockSelector) || [])];
  if (root.nodeType === Node.ELEMENT_NODE && root.matches(blockSelector)) {
    blocks.unshift(root);
  }
  return blocks;
}

function init(root = document) {
  getBlocks(root).forEach((el) => {
    if (instances.has(el)) return;

    const wrap = el.closest('.product-carousel--sobe');
    if (!wrap) return;

    const swiper = new Swiper(el, {
      modules: [Navigation],
      slidesPerView: 2,
      spaceBetween: 12,
      loop: true,
      navigation: {
        nextEl: wrap.querySelector('.carousel-btn-next'),
        prevEl: wrap.querySelector('.carousel-btn-prev'),
      },
      breakpoints: {
        640:  { slidesPerView: 3, spaceBetween: 16 },
        1024: { slidesPerView: 4, spaceBetween: 24 },
      },
    });

    instances.set(el, { swiper });
  });
}

function destroy() {
  document.querySelectorAll(blockSelector).forEach((el) => {
    const state = instances.get(el);
    if (!state) return;

    state.swiper.destroy(true, true);
    instances.delete(el);
  });
}

registerReinit('product-carousel', { init, destroy });

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => init(document));
} else {
  init(document);
}

export { init, destroy };

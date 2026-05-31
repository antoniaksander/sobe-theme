import Swiper from 'swiper';
import { Navigation } from 'swiper/modules';
import { registerReinit } from '../../js/sobe-reinit.js';

const instances = new WeakMap();
const blockSelector = '.sobe-product-categories-grid';
const mqDesktop = '(min-width: 48rem)';

function getBlocks(root = document) {
  const blocks = [...(root.querySelectorAll?.(blockSelector) || [])];
  if (root.nodeType === Node.ELEMENT_NODE && root.matches(blockSelector)) {
    blocks.unshift(root);
  }
  return blocks;
}

function init(root = document) {
  getBlocks(root).forEach((wrap) => {
    if (instances.has(wrap)) return;

    const el = wrap.querySelector('.sobe-pc-grid-swiper');
    if (!el) return;

    const prev = wrap.querySelector('.sobe-pc-grid-nav--prev');
    const next = wrap.querySelector('.sobe-pc-grid-nav--next');
    const mq = window.matchMedia(mqDesktop);
    const state = {
      apply: null,
      mq,
      swiper: null,
    };

    const syncNav = () => {
      if (!state.swiper || !prev || !next) {
        return;
      }
      prev.classList.toggle('swiper-button-disabled', state.swiper.isBeginning);
      next.classList.toggle('swiper-button-disabled', state.swiper.isEnd);
    };

    state.apply = () => {
      if (mq.matches) {
        if (state.swiper) {
          state.swiper.destroy(true, true);
          state.swiper = null;
        }
        if (prev) {
          prev.classList.remove('swiper-button-disabled');
        }
        if (next) {
          next.classList.remove('swiper-button-disabled');
        }
        return;
      }

      if (state.swiper) {
        return;
      }

      state.swiper = new Swiper(el, {
        modules: [Navigation],
        slidesPerView: 1.08,
        spaceBetween: 12,
        autoHeight: false,
        watchOverflow: true,
        navigation: {
          prevEl: prev,
          nextEl: next,
        },
        on: {
          afterInit: syncNav,
          slideChange: syncNav,
          reachBeginning: syncNav,
          reachEnd: syncNav,
        },
      });
      syncNav();
    };

    state.apply();
    mq.addEventListener('change', state.apply);

    instances.set(wrap, state);
  });
}

function destroy() {
  document.querySelectorAll(blockSelector).forEach((wrap) => {
    const state = instances.get(wrap);
    if (!state) return;

    state.mq.removeEventListener('change', state.apply);
    if (state.swiper) {
      state.swiper.destroy(true, true);
    }
    instances.delete(wrap);
  });
}

registerReinit('product-categories-grid', { init, destroy });

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => init(document));
} else {
  init(document);
}

export { init, destroy };

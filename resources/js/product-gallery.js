import Swiper from 'swiper';
import { Navigation, Pagination, Thumbs, FreeMode, Manipulation } from 'swiper/modules';
import { registerReinit } from './sobe-reinit.js';
// CSS imported via woocommerce.css (which is enqueued by WP) — not here,
// because Vite extracts JS-imported CSS into a separate file that wp_enqueue_script
// does not automatically load.

const galleryInstances = new WeakMap();

const isSafeHttpUrl = (value) => {
  if (typeof value !== 'string' || value.length === 0) return false;

  try {
    const url = new URL(value, window.location.origin);
    return url.protocol === 'http:' || url.protocol === 'https:';
  } catch {
    return false;
  }
};

const createVariationSlide = ({ src, srcset, alt, full }) => {
  const slide = document.createElement('div');
  slide.className = 'swiper-slide';
  slide.dataset.full = full;

  const img = document.createElement('img');
  img.src = src;
  if (srcset) {
    img.srcset = srcset;
  }
  img.alt = alt;
  img.loading = 'lazy';

  slide.appendChild(img);

  return slide;
};

function getGalleryRoots(root = document) {
  const roots = [];

  if (root?.matches?.('.pdp-gallery')) {
    roots.push(root);
  }

  root?.querySelectorAll?.('.pdp-gallery').forEach((gallery) => {
    roots.push(gallery);
  });

  return roots;
}

function hasWooVariationFormInstance($, $form) {
  if ($form.data('wc_variation_form')) {
    return true;
  }

  const events = $._data?.($form[0], 'events') ?? {};
  const hasHandler = Object.values(events).some((handlers) =>
    handlers?.some?.((handler) => handler.namespace?.includes('wc-variation-form'))
  );

  if (hasHandler) {
    $form.data('wc_variation_form', true);
  }

  return hasHandler;
}

function ensureVariationForm($, form) {
  if (!form) {
    return;
  }

  if (!$.fn.wc_variation_form) {
    console.warn('[sobe product-gallery] $.fn.wc_variation_form is unavailable; reloading for a full WooCommerce PDP bootstrap.');
    window.location.reload();
    return;
  }

  const $form = $(form);

  $form.on('wc_variation_form.sobePdpSwup', (_event, variationForm) => {
    $form.data('wc_variation_form', variationForm || true);
  });

  if (!hasWooVariationFormInstance($, $form)) {
    $form.wc_variation_form();
    $form.data('wc_variation_form', true);
  }

  $form.trigger('check_variations');
}

function initQuantityButtons(form, state) {
  const qtyWrapper = form?.querySelector('.quantity');
  if (!qtyWrapper || qtyWrapper.querySelector('.qty-btn')) {
    return;
  }

  const input = qtyWrapper.querySelector('input.qty');
  if (!input) {
    return;
  }

  const makeBtn = (label, sign) => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = `qty-btn qty-${sign === '+' ? 'plus' : 'minus'}`;
    btn.setAttribute('aria-label', label);
    btn.textContent = sign;
    return btn;
  };

  const minus = makeBtn('Decrease quantity', '−');
  const plus = makeBtn('Increase quantity', '+');
  const { signal } = state.abortController;

  minus.addEventListener('click', () => {
    const min = parseFloat(input.min) || 1;
    const step = parseFloat(input.step) || 1;
    const val = parseFloat(input.value) || min;
    if (val - step >= min) {
      input.value = val - step;
      input.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }, { signal });

  plus.addEventListener('click', () => {
    const max = parseFloat(input.max) || Infinity;
    const step = parseFloat(input.step) || 1;
    const val = parseFloat(input.value) || 1;
    if (!isFinite(max) || val + step <= max) {
      input.value = val + step;
      input.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }, { signal });

  qtyWrapper.insertBefore(minus, input);
  qtyWrapper.appendChild(plus);
  state.quantityButtons.push(minus, plus);
}

function initProductGallery(root = document) {
  getGalleryRoots(root).forEach((galleryRoot) => {
    if (galleryInstances.has(galleryRoot)) {
      return;
    }

    const mainEl = galleryRoot.querySelector('#pdp-swiper-main');
    const thumbEl = galleryRoot.querySelector('#pdp-swiper-thumbs');
    const productRoot = galleryRoot.closest('.product');
    const form = productRoot?.querySelector('form.cart') ?? document.querySelector('.single-product form.cart');

    if (!mainEl || !thumbEl || !mainEl.querySelectorAll('.swiper-slide').length) {
      return;
    }

    const state = {
      galleryRoot,
      mainEl,
      thumbEl,
      form,
      abortController: new AbortController(),
      mainSwiper: null,
      thumbsSwiper: null,
      variationSlideIndex: null,
      originalPriceHTML: null,
      quantityButtons: [],
    };

    initQuantityButtons(form, state);

    state.thumbsSwiper = new Swiper(thumbEl, {
      modules: [FreeMode, Manipulation],
      spaceBetween: 8,
      slidesPerView: 'auto',
      freeMode: true,
      watchSlidesProgress: true,
    });

    state.mainSwiper = new Swiper(mainEl, {
      modules: [Navigation, Pagination, Thumbs, Manipulation],
      slidesPerView: 1.5,
      spaceBetween: 6,
      slidesPerGroupSkip: 1,
      slidesOffsetBefore: 16,
      grabCursor: true,
      touchReleaseOnEdges: true,
      rewind: true,
      roundLengths: true,

      navigation: {
        nextEl: mainEl.querySelector('.swiper-button-next'),
        prevEl: mainEl.querySelector('.swiper-button-prev'),
      },
      pagination: {
        el: mainEl.querySelector('.swiper-pagination'),
        type: 'fraction',
      },
      thumbs: {
        swiper: state.thumbsSwiper,
      },
    });

    mainEl.style.cursor = 'zoom-in';

    mainEl.addEventListener('click', (event) => {
      const slide = event.target.closest('.swiper-slide');
      if (!slide) return;
      if (
        typeof PhotoSwipe === 'undefined' ||
        typeof PhotoSwipeUI_Default === 'undefined'
      ) {
        return;
      }

      const slides = [
        ...mainEl.querySelectorAll('.swiper-slide:not(.swiper-slide-duplicate)'),
      ];
      const items = slides.map((itemSlide) => {
        const img = itemSlide.querySelector('img');
        return {
          src: itemSlide.dataset.full,
          w: img?.naturalWidth || 1800,
          h: img?.naturalHeight || 1800,
        };
      });

      const pswpEl = document.querySelector('.pswp');
      if (!pswpEl) return;

      new PhotoSwipe(pswpEl, PhotoSwipeUI_Default, items, {
        index: state.mainSwiper.realIndex,
        bgOpacity: 0.9,
        shareEl: false,
      }).init();
    }, { signal: state.abortController.signal });

    bindVariationHandlers(state);

    galleryInstances.set(galleryRoot, state);

    if (Object.hasOwn(window, '__sobeDebugPdpGalleryInstance')) {
      window.__sobeDebugPdpGalleryInstance = state;
    }
  });
}

function bindVariationHandlers(state) {
  const $ = window.jQuery;
  if (!$ || !state.form) return;

  const { form } = state;
  const $form = $(form);
  const staticPriceEl = document.querySelector(
    '.pdp-summary p.price, .pdp-summary span.price',
  );

  $form.off('.sobePdpSwup');
  ensureVariationForm($, form);

  $form
    .on('found_variation.sobePdpSwup', (_event, variation) => {
      if (staticPriceEl && variation.price_html) {
        if (state.originalPriceHTML === null) {
          state.originalPriceHTML = staticPriceEl.innerHTML;
        }
        staticPriceEl.innerHTML = variation.price_html;
      }

      if (!variation.image || !variation.image.src) return;

      const src = variation.image.src;
      const full = variation.image.full_src || src;

      if (!isSafeHttpUrl(src) || !isSafeHttpUrl(full)) return;

      if (state.variationSlideIndex !== null) {
        state.mainSwiper.removeSlide(state.variationSlideIndex);
        state.thumbsSwiper.removeSlide(state.variationSlideIndex);
        state.variationSlideIndex = null;
      }

      const slides = [
        ...state.mainEl.querySelectorAll('.swiper-slide:not(.swiper-slide-duplicate)'),
      ];
      const existingIdx = slides.findIndex((slide) => slide.dataset.full === full);
      if (existingIdx !== -1) {
        state.mainSwiper.slideTo(existingIdx);
        return;
      }

      const srcset = variation.image.srcset || '';
      const alt = variation.image.alt || '';
      const newIdx = state.mainSwiper.slides.length;

      state.mainSwiper.addSlide(
        newIdx,
        createVariationSlide({ src, srcset, alt, full }),
      );
      state.thumbsSwiper.addSlide(
        newIdx,
        createVariationSlide({ src, srcset: '', alt, full: src }),
      );

      state.variationSlideIndex = newIdx;
      state.mainSwiper.slideTo(newIdx);
    });

  $form
    .on('reset_data.sobePdpSwup', () => {
      if (staticPriceEl && state.originalPriceHTML !== null) {
        staticPriceEl.innerHTML = state.originalPriceHTML;
        state.originalPriceHTML = null;
      }

      if (state.variationSlideIndex !== null) {
        state.mainSwiper.removeSlide(state.variationSlideIndex);
        state.thumbsSwiper.removeSlide(state.variationSlideIndex);
        state.variationSlideIndex = null;
      }
      state.mainSwiper.slideTo(0);
    });
}

function destroyProductGallery() {
  document.querySelectorAll('.pdp-gallery').forEach((galleryRoot) => {
    const state = galleryInstances.get(galleryRoot);
    if (!state) {
      return;
    }

    state.abortController.abort();
    window.jQuery?.(state.form).off('.sobePdpSwup');
    state.mainSwiper?.destroy(true, true);
    state.thumbsSwiper?.destroy(true, true);
    state.quantityButtons.forEach((button) => button.remove());

    if (
      Object.hasOwn(window, '__sobeDebugPdpGalleryInstance') &&
      window.__sobeDebugPdpGalleryInstance === state
    ) {
      window.__sobeDebugPdpGalleryInstance = null;
    }

    galleryInstances.delete(galleryRoot);
  });
}

registerReinit('product-gallery', {
  init: initProductGallery,
  destroy: destroyProductGallery,
});

document.addEventListener('DOMContentLoaded', () => {
  initProductGallery(document);
});

export { destroyProductGallery, initProductGallery };

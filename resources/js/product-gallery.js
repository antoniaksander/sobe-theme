import Swiper from 'swiper';
import { Navigation, Pagination, Thumbs, FreeMode } from 'swiper/modules';
// CSS imported via woocommerce.css (which is enqueued by WP) — not here,
// because Vite extracts JS-imported CSS into a separate file that wp_enqueue_script
// does not automatically load.

document.addEventListener('DOMContentLoaded', () => {
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

  // ── Quantity +/− buttons ──────────────────────────────────────────────────
  const qtyWrapper = document.querySelector('form.cart .quantity');
  if (qtyWrapper) {
    const input = qtyWrapper.querySelector('input.qty');
    if (input) {
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

      minus.addEventListener('click', () => {
        const min = parseFloat(input.min) || 1;
        const step = parseFloat(input.step) || 1;
        const val = parseFloat(input.value) || min;
        if (val - step >= min) {
          input.value = val - step;
          input.dispatchEvent(new Event('change', { bubbles: true }));
        }
      });

      plus.addEventListener('click', () => {
        const max = parseFloat(input.max) || Infinity;
        const step = parseFloat(input.step) || 1;
        const val = parseFloat(input.value) || 1;
        if (!isFinite(max) || val + step <= max) {
          input.value = val + step;
          input.dispatchEvent(new Event('change', { bubbles: true }));
        }
      });

      qtyWrapper.insertBefore(minus, input);
      qtyWrapper.appendChild(plus);
    }
  }

  const mainEl = document.getElementById('pdp-swiper-main');
  const thumbEl = document.getElementById('pdp-swiper-thumbs');
  if (!mainEl || !thumbEl) return;

  if (!mainEl.querySelectorAll('.swiper-slide').length) return;

  const initSwiperGallery = () => {
    // ── Swiper: Thumbs (must init first for sync) ───────────────────────────
    const thumbsSwiper = new Swiper(thumbEl, {
      modules: [FreeMode],
      spaceBetween: 8,
      slidesPerView: 'auto',
      freeMode: true,
      watchSlidesProgress: true,
    });

    // ── Swiper: Main slider ─────────────────────────────────────────────────
    const mainSwiper = new Swiper(mainEl, {
      modules: [Navigation, Pagination, Thumbs],
      slidesPerView: 1.5,
      spaceBetween: 6,
      slidesPerGroupSkip: 1,
      slidesOffsetBefore: 16,
      // centeredSlides: true,
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
        swiper: thumbsSwiper,
      },
    });

    // ── PhotoSwipe bridge ───────────────────────────────────────────────────
    mainEl.style.cursor = 'zoom-in';

    mainEl.addEventListener('click', (e) => {
      const slide = e.target.closest('.swiper-slide');
      if (!slide) return;
      if (
        typeof PhotoSwipe === 'undefined' ||
        typeof PhotoSwipeUI_Default === 'undefined'
      )
        return;

      const slides = [
        ...mainEl.querySelectorAll('.swiper-slide:not(.swiper-slide-duplicate)'),
      ];
      const items = slides.map((s) => {
        const img = s.querySelector('img');
        return {
          src: s.dataset.full,
          w: img?.naturalWidth || 1800,
          h: img?.naturalHeight || 1800,
        };
      });

      const pswpEl = document.querySelector('.pswp');
      if (!pswpEl) return;

      new PhotoSwipe(pswpEl, PhotoSwipeUI_Default, items, {
        index: mainSwiper.realIndex,
        bgOpacity: 0.9,
        shareEl: false,
      }).init();
    });

    // ── jQuery required for WC variation events ─────────────────────────────
    const $ = window.jQuery;
    if (!$) return;

    let variationSlideIndex = null;

    // Static price element — updated in-place on variation select so the price always
    // appears in the correct position (after title/reviews, before swatches).
    // The variation price inside .single_variation is hidden via CSS.
    const staticPriceEl = document.querySelector(
      '.pdp-summary p.price, .pdp-summary span.price',
    );
    let originalPriceHTML = null;

    $(document).on('found_variation', '.variations_form', (_e, variation) => {
      // Update static price with variation-specific price HTML
      if (staticPriceEl && variation.price_html) {
        if (originalPriceHTML === null)
          originalPriceHTML = staticPriceEl.innerHTML;
        staticPriceEl.innerHTML = variation.price_html;
      }

      if (!variation.image || !variation.image.src) return;

      const src = variation.image.src;
      const full = variation.image.full_src || src;

      if (!isSafeHttpUrl(src) || !isSafeHttpUrl(full)) return;

      // Remove previously injected variation slide first
      if (variationSlideIndex !== null) {
        mainSwiper.removeSlide(variationSlideIndex);
        thumbsSwiper.removeSlide(variationSlideIndex);
        variationSlideIndex = null;
      }

      // Reuse existing slide if image already in the gallery
      const slides = [
        ...mainEl.querySelectorAll('.swiper-slide:not(.swiper-slide-duplicate)'),
      ];
      const existingIdx = slides.findIndex((s) => s.dataset.full === full);
      if (existingIdx !== -1) {
        mainSwiper.slideTo(existingIdx);
        return;
      }

      // Inject a temporary variation slide
      const srcset = variation.image.srcset || '';
      const alt = variation.image.alt || '';
      const newIdx = mainSwiper.slides.length;

      mainSwiper.addSlide(
        newIdx,
        createVariationSlide({ src, srcset, alt, full }),
      );
      thumbsSwiper.addSlide(
        newIdx,
        createVariationSlide({ src, srcset: '', alt, full: src }),
      );

      variationSlideIndex = newIdx;
      mainSwiper.slideTo(newIdx);
    });

    $(document).on('reset_data', '.variations_form', () => {
      // Restore original range price
      if (staticPriceEl && originalPriceHTML !== null) {
        staticPriceEl.innerHTML = originalPriceHTML;
        originalPriceHTML = null;
      }

      if (variationSlideIndex !== null) {
        mainSwiper.removeSlide(variationSlideIndex);
        thumbsSwiper.removeSlide(variationSlideIndex);
        variationSlideIndex = null;
      }
      mainSwiper.slideTo(0);
    });
  };

  // Defer Swiper init until the browser is idle or the user first interacts —
  // whichever comes first. The gallery is above-fold so IntersectionObserver
  // fires immediately; idle/interaction deferral is the correct strategy here.
  let swiperInitialized = false;
  const initOnce = () => {
    if (swiperInitialized) return;
    swiperInitialized = true;
    document.removeEventListener('touchstart', initOnce);
    document.removeEventListener('scroll', initOnce);
    document.removeEventListener('mousemove', initOnce);
    initSwiperGallery();
  };

  document.addEventListener('touchstart', initOnce, { once: true, passive: true });
  document.addEventListener('scroll', initOnce, { once: true, passive: true });
  document.addEventListener('mousemove', initOnce, { once: true, passive: true });

  'requestIdleCallback' in window
    ? requestIdleCallback(initOnce, { timeout: 500 })
    : setTimeout(initOnce, 200);
});

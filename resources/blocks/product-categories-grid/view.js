import Swiper from 'swiper';
import { Navigation } from 'swiper/modules';

const mqDesktop = '(min-width: 48rem)';

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.sobe-pc-grid-swiper').forEach((el) => {
    const wrap = el.closest('.sobe-product-categories-grid');
    if (!wrap || el.dataset.pcSwiperBound) {
      return;
    }
    el.dataset.pcSwiperBound = '1';

    const prev = wrap.querySelector('.sobe-pc-grid-nav--prev');
    const next = wrap.querySelector('.sobe-pc-grid-nav--next');
    const mq = window.matchMedia(mqDesktop);

    let swiper = null;

    const syncNav = () => {
      if (!swiper || !prev || !next) {
        return;
      }
      prev.classList.toggle('swiper-button-disabled', swiper.isBeginning);
      next.classList.toggle('swiper-button-disabled', swiper.isEnd);
    };

    const apply = () => {
      if (mq.matches) {
        if (swiper) {
          swiper.destroy(true, true);
          swiper = null;
        }
        if (prev) {
          prev.classList.remove('swiper-button-disabled');
        }
        if (next) {
          next.classList.remove('swiper-button-disabled');
        }
        return;
      }

      if (swiper) {
        return;
      }

      swiper = new Swiper(el, {
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

    apply();
    mq.addEventListener('change', apply);
  });
});

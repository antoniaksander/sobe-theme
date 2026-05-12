import Swiper from 'swiper';
import { Autoplay, Navigation } from 'swiper/modules';

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.sobe-reviews-slider').forEach((block) => {
    const delay = parseInt(block.dataset.autoplayDelay ?? '5000', 10);
    const swiperEl = block.querySelector('.reviews-slider-swiper');
    if (!swiperEl) return;

    const prevBtn = block.querySelector('.reviews-slider__btn--prev');
    const nextBtn = block.querySelector('.reviews-slider__btn--next');
    const dots    = block.querySelectorAll('.reviews-slider__dot');

    const swiper = new Swiper(swiperEl, {
      modules: [Autoplay, Navigation],
      loop: true,
      speed: 600,
      autoplay: {
        delay,
        disableOnInteraction: false,
        pauseOnMouseEnter: true,
      },
      navigation: {
        prevEl: prevBtn ?? null,
        nextEl: nextBtn ?? null,
      },
      on: {
        slideChange(sw) {
          dots.forEach((dot, i) => {
            dot.classList.toggle('is-active', i === sw.realIndex);
          });
        },
        init(sw) {
          dots.forEach((dot, i) => {
            dot.classList.toggle('is-active', i === sw.realIndex);
          });
        },
      },
    });

    // Dot clicks
    dots.forEach((dot, i) => {
      dot.addEventListener('click', () => swiper.slideToLoop(i));
    });
  });
});

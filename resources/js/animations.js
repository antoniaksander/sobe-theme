import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { registerReinit } from './sobe-reinit.js';

gsap.registerPlugin(ScrollTrigger);

const presets = {
  'fade-up': { y: 40, opacity: 0, duration: 0.8, ease: 'power2.out' },
  'fade-in': { opacity: 0, duration: 0.6, ease: 'power2.out' },
  'scale-in': { scale: 0.9, opacity: 0, duration: 0.6, ease: 'back.out(1.2)' },
  'slide-left': { x: -40, opacity: 0, duration: 0.8, ease: 'power2.out' },
  'slide-right': { x: 40, opacity: 0, duration: 0.8, ease: 'power2.out' },
};

let pageContext = null;

function getScopedElements(root, selector) {
  const elements = [...(root.querySelectorAll?.(selector) || [])];
  if (root.nodeType === Node.ELEMENT_NODE && root.matches(selector)) {
    elements.unshift(root);
  }
  return elements;
}

// Query only elements not yet processed — makes this function safe to call
// repeatedly after WooCommerce AJAX updates without duplicating animations.
function initAnimationBus(root = document) {
  destroyAnimationBus();

  pageContext = gsap.context(() => {
    initPageAnimations(root);
  }, root);
}

function initPageAnimations(root = document) {
  // Bridge: `.animate-{preset}` CSS classes (added via block editor's
  // "Additional CSS class(es)" field) are converted to data-animate so the
  // standard preset handler picks them up without any extra logic.
  const classSelector = Object.keys(presets)
    .map((p) => `.animate-${p}:not([data-animate])`)
    .join(',');
  getScopedElements(root, classSelector).forEach((el) => {
    const type = Object.keys(presets).find((p) =>
      el.classList.contains(`animate-${p}`),
    );
    if (type) el.dataset.animate = type;
  });

  gsap.matchMedia().add('(prefers-reduced-motion: no-preference)', () => {
    const elements = getScopedElements(
      root,
      '[data-animate]:not([data-animated])',
    );

    elements.forEach((el) => {
      el.dataset.animated = 'true';

      const type = el.dataset.animate;

      if (type === 'hero-content') {
        // fromTo with explicit opacity:1 target — avoids GSAP misreading computed
        // opacity:0 when the WebGL canvas mix-blend-mode compositing group is active.
        gsap.fromTo(
          el.querySelectorAll('h1, h2, p, a, button'),
          { y: 50, opacity: 0 },
          {
            y: 0,
            opacity: 1,
            duration: 1,
            stagger: 0.15,
            ease: 'power4.out',
            delay: 0.2,
            clearProps: 'opacity,y,transform',
          },
        );
        return;
      }

      if (type === 'product-feature') {
        gsap.fromTo(
          el.querySelectorAll(':scope > div'),
          { y: 40, opacity: 0 },
          {
            scrollTrigger: {
              trigger: el,
              start: 'top 80%',
              toggleActions: 'play none none reverse',
            },
            y: 0,
            opacity: 1,
            duration: 0.8,
            stagger: 0.15,
            ease: 'power2.out',
            clearProps: 'opacity,transform',
          },
        );
        return;
      }

      if (type === 'brand-carousel') {
        gsap.fromTo(
          el,
          { y: 60, opacity: 0 },
          {
            scrollTrigger: {
              trigger: el,
              start: 'top 85%',
              toggleActions: 'play none none reverse',
            },
            y: 0,
            opacity: 1,
            duration: 1.2,
            ease: 'power4.out',
            clearProps: 'opacity,transform',
          },
        );
        return;
      }

      const preset = presets[type];
      if (!preset) return;

      gsap.fromTo(
        el,
        {
          y: preset.y ?? 0,
          x: preset.x ?? 0,
          scale: preset.scale ?? 1,
          opacity: preset.opacity ?? 0,
        },
        {
          scrollTrigger: {
            trigger: el,
            start: 'top 85%',
            toggleActions: 'play none none reverse',
          },
          y: 0,
          x: 0,
          scale: 1,
          opacity: 1,
          duration: preset.duration ?? 0.8,
          ease: preset.ease ?? 'power2.out',
          clearProps: 'opacity,transform',
        },
      );
    });
  });
}

function destroyAnimationBus() {
  pageContext?.revert();
  pageContext = null;
}

function resetAnimationMarks(root = document) {
  if (root.nodeType === Node.ELEMENT_NODE && root.hasAttribute('data-animated')) {
    root.removeAttribute('data-animated');
  }

  root.querySelectorAll?.('[data-animated]').forEach((el) => {
    el.removeAttribute('data-animated');
  });
}

let stickyHeaderInitialized = false;

// Persistent shell animations live outside the page gsap.context because their
// DOM (e.g. .site-header) is outside #main and survives page navigations.
// Tearing them down on every transition would break sticky header behavior.
function initStickyHeader() {
  if (stickyHeaderInitialized) return;

  const header = document.querySelector('.site-header');
  if (!header) return;

  stickyHeaderInitialized = true;

  // Always defined so cart drawer can call it safely regardless of motion preference
  window.showSiteHeader = () => {};

  gsap.matchMedia().add('(prefers-reduced-motion: no-preference)', () => {
    const headerAnim = gsap.to(header, {
      yPercent: -100,
      duration: 0.35,
      ease: 'power2.inOut',
      paused: true,
    });

    ScrollTrigger.create({
      start: 'top top-=80',
      end: '99999',
      onUpdate(self) {
        if (self.direction === 1) {
          headerAnim.play(); // scrolling down → hide
        } else {
          headerAnim.reverse(); // scrolling up   → show
        }
      },
    });

    window.showSiteHeader = () => headerAnim.reverse();
  });
}

// Debounced ScrollTrigger.refresh() on resize so trigger positions stay accurate
// after layout shifts (viewport resize, content injection, font load, etc.).
let _refreshTimer;
window.addEventListener('resize', () => {
  clearTimeout(_refreshTimer);
  _refreshTimer = setTimeout(() => ScrollTrigger.refresh(), 150);
});

initStickyHeader();

registerReinit('animation-bus', {
  init: initAnimationBus,
  destroy: destroyAnimationBus,
});

const bootAnimationBus = () => {
  if (!pageContext) {
    initAnimationBus(document);
  }
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootAnimationBus);
} else {
  bootAnimationBus();
}

window.gsap = gsap;
window.ScrollTrigger = ScrollTrigger;
window.initAnimationBus = initAnimationBus;

export {
  initAnimationBus,
  destroyAnimationBus,
  resetAnimationMarks,
  initStickyHeader,
  gsap,
  ScrollTrigger,
};

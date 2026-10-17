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

// Client-registered animation types (registerAnimation). Checked before the
// built-in types, so a client can add its own data-animate="…" values.
const customAnimations = new Map();

/**
 * Register a handler for elements with data-animate="{type}". The handler
 * runs inside the animation bus's gsap context (so it is reverted with it)
 * and receives (el, { gsap, ScrollTrigger }).
 */
function registerAnimation(type, handler) {
  if (typeof type === 'string' && typeof handler === 'function') {
    customAnimations.set(type, handler);
  }
}

// Query only elements not yet processed — makes this function safe to call
// repeatedly after WooCommerce AJAX updates without duplicating animations.
//
// The actual scan/ScrollTrigger creation is deferred one frame. Block view
// scripts load in the footer, after this module's own DOMContentLoaded
// listener, so a block that pins content (inflating document height via a
// pin-spacer) hasn't run yet when this fires synchronously. Anything below
// such a block gets measured against a too-short document, and if that puts
// it within the initial viewport's "already entered" range, ScrollTrigger
// fires its reveal immediately instead of waiting for the user to scroll
// there. Deferring by a frame lets footer scripts finish their initial
// layout-affecting setup first.
function initAnimationBus(root = document) {
  if (!pageContext) {
    pageContext = gsap.context(() => {}, root);
  }

  // Note: the context's SCOPE was set when first created; pageContext.add()
  // does not re-scope. Callers needing a different root scope must call
  // destroyAnimationBus() first, then initAnimationBus(newRoot). This is the
  // explicit lifecycle pattern used by the registry and by AJAX refresh handlers.
  requestAnimationFrame(() => {
    pageContext.add(() => {
      initPageAnimations(root);
    });
  });
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

  // Skip all animation setup (gsap.matchMedia + ScrollTrigger work + forced
  // reflows) on roots with nothing to animate (cart, checkout, account, etc.).
  if (!getScopedElements(root, '[data-animate]:not([data-animated])').length) {
    return;
  }

  gsap.matchMedia().add('(prefers-reduced-motion: no-preference)', () => {
    const elements = getScopedElements(
      root,
      '[data-animate]:not([data-animated])',
    );

    elements.forEach((el) => {
      el.dataset.animated = 'true';

      const type = el.dataset.animate;

      const custom = customAnimations.get(type);
      if (custom) {
        custom(el, { gsap, ScrollTrigger });
        return;
      }

      if (type === 'hero-content') {
        // fromTo with explicit opacity:1 target — avoids GSAP misreading computed
        // opacity:0 when the WebGL canvas mix-blend-mode compositing group is active.
        // data-animate-targets narrows which children animate.
        gsap.fromTo(
          el.querySelectorAll(el.dataset.animateTargets || 'h1, h2, p, a, button'),
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

  // A header layout that must stay visible (e.g. a fixed, transparent one)
  // opts out of hide-on-scroll with data-hide-on-scroll="false".
  if (header.dataset.hideOnScroll === 'false') return;

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

// One more refresh on window 'load' (fires after every script, including
// footer-loaded block view scripts, has run its own rAF-chained setup) as a
// safety net beyond initAnimationBus()'s one-frame defer: a block that pins
// content may schedule its pin-spacer refresh in the same frame, after ours.
window.addEventListener('load', () => ScrollTrigger.refresh());

// Not called eagerly here: app.js's scheduleIdle(() => initStickyHeader())
// is the intended, deferred caller. An eager call here ran first and set
// initStickyHeader's own guard, turning the idle deferral into a no-op.

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
  registerAnimation,
  initAnimationBus,
  destroyAnimationBus,
  resetAnimationMarks,
  initStickyHeader,
  gsap,
  ScrollTrigger,
};

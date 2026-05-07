import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import Lenis from 'lenis';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { initAnimationBus, initStickyHeader } from './animations.js';

gsap.registerPlugin(ScrollTrigger);

const getThemeCartParams = () => window.themeCartParams ?? {};

const getAddedToCartText = () =>
  getThemeCartParams().addedToCartText ?? 'Product added to cart';

const getCartOpenedText = () =>
  getThemeCartParams().cartOpenedText ??
  'Product added to cart. Your cart is now open.';

const getAddToCartErrorText = () =>
  getThemeCartParams().addToCartErrorText ?? 'Could not add product to cart.';

const getNetworkErrorText = () =>
  getThemeCartParams().networkErrorText ??
  'Something went wrong. Please try again.';

const getStoreApiCartUrl = () =>
  getThemeCartParams().storeApiCartUrl ?? '/wp-json/wc/store/v1/cart';

const getStoreApiAddUrl = () =>
  getThemeCartParams().storeApiAddUrl ?? '/wp-json/wc/store/v1/cart/add-item';

const getStoreApiNonce = () => getThemeCartParams().storeApiNonce ?? '';

function getCartCount(cart) {
  return (
    cart.items?.reduce(
      (sum, item) => sum + (parseInt(item.quantity, 10) || 0),
      0,
    ) ?? 0
  );
}

function normalizeToast(notice) {
  const id = notice.id || `toast-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
  return { ...notice, id, timestamp: Date.now() };
}

function parseToastFragmentData(fragments) {
  if (!fragments?.sobe_toast_data) {
    return [];
  }

  try {
    const toasts = JSON.parse(fragments.sobe_toast_data);
    return Array.isArray(toasts) ? toasts : [];
  } catch (_error) {
    return [];
  }
}

function dispatchCartItemAdded(detail) {
  window.dispatchEvent(
    new CustomEvent('sobe:cart:item-added', {
      detail,
    }),
  );
}

function buildStoreApiPayload(form) {
  const formData = new FormData(form);
  const quantity = parseInt(formData.get('quantity') || '1', 10) || 1;
  const variationId = parseInt(formData.get('variation_id') || '0', 10) || 0;
  const productId =
    variationId ||
    parseInt(formData.get('add-to-cart') || formData.get('product_id') || '0', 10) ||
    parseInt(form.querySelector('button[name="add-to-cart"]')?.value || '0', 10) ||
    0;

  if (!productId) {
    return null;
  }

  const payload = {
    id: productId,
    quantity,
  };

  if (variationId) {
    const variation = [];

    for (const [key, value] of formData.entries()) {
      if (!key.startsWith('attribute_') || value === '') {
        continue;
      }

      variation.push({
        attribute: key.replace(/^attribute_/, ''),
        value: `${value}`,
      });
    }

    payload.variation = variation;
  }

  return payload;
}

function isVariableProductForm(form) {
  return form.classList.contains('variations_form');
}

function getSupportedProductType(form) {
  const productRoot = form.closest('.product');
  if (!productRoot) {
    return null;
  }

  if (
    isVariableProductForm(form) ||
    productRoot.classList.contains('product-type-variable')
  ) {
    return 'variable';
  }

  if (productRoot.classList.contains('product-type-simple')) {
    return 'simple';
  }

  return null;
}

function shouldHandleWithStoreApi(form) {
  const productType = getSupportedProductType(form);
  if (productType === 'variable') {
    const variationId = parseInt(
      form.querySelector('input[name="variation_id"]')?.value || '0',
      10,
    );

    return variationId > 0;
  }

  return productType === 'simple';
}

async function parseResponseBody(response) {
  const contentType = response.headers.get('content-type') || '';

  if (contentType.includes('application/json')) {
    return {
      kind: 'json',
      body: await response.json(),
    };
  }

  return {
    kind: 'text',
    body: await response.text(),
  };
}

async function addSingleProductViaStoreApi(form, trigger) {
  const payload = buildStoreApiPayload(form);
  if (!payload) {
    throw new Error('Missing product payload');
  }

  const response = await fetch(getStoreApiAddUrl(), {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      Nonce: getStoreApiNonce(),
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  });

  const result = await parseResponseBody(response);

  if (!response.ok) {
    const message =
      result.kind === 'json'
        ? result.body?.message || result.body?.data?.message || getAddToCartErrorText()
        : getAddToCartErrorText();

    throw new Error(message);
  }

  if (result.kind !== 'json') {
    throw new Error(getAddToCartErrorText());
  }

  const cart = result.body;
  const count = getCartCount(cart);

  window.dispatchEvent(
    new CustomEvent('cart-updated', {
      detail: {
        items: cart.items || [],
        count,
      },
    }),
  );

  dispatchCartItemAdded({
    source: 'store-api',
    trigger,
    productId: payload.id,
    quantity: payload.quantity,
    cart,
    count,
  });
}

function initSingleProductAddToCart() {
  const form = document.querySelector('.single-product form.cart');
  if (!form) return;

  form.addEventListener('submit', async (event) => {
    if (!shouldHandleWithStoreApi(form)) {
      return;
    }

    event.preventDefault();

    const button = form.querySelector('button.single_add_to_cart_button');
    const $button = button && typeof jQuery !== 'undefined' ? jQuery(button) : null;

    $button?.addClass('loading').prop('disabled', true);

    try {
      await addSingleProductViaStoreApi(form, button || event.submitter || document.activeElement);
    } catch (error) {
      Alpine.store('toastManager').add({
        type: 'error',
        message:
          error instanceof Error && error.message
            ? error.message
            : getNetworkErrorText(),
      });
    } finally {
      $button?.removeClass('loading').prop('disabled', false);
    }
  });
}

Alpine.store('toastManager', {
  notices: [],

  add(notice) {
    const item = normalizeToast(notice);
    this.notices.push(item);

    setTimeout(() => {
      this.remove(item.id);
    }, 4000);
  },

  remove(id) {
    this.notices = this.notices.filter((notice) => notice.id !== id);
  },
});

let lenis;

Alpine.data('app', () => ({
  navOpen: false,
  cartOpen: false,
  dark: false,
  cartAnnouncement: '',
  lastCartTrigger: null,

  init() {
    initSingleProductAddToCart();

    if (document.querySelector('[data-dark-toggle]')) {
      this.dark =
        localStorage.getItem('theme') === 'dark' ||
        (!localStorage.getItem('theme') &&
          window.matchMedia('(prefers-color-scheme: dark)').matches);
    }

    this.$watch('cartOpen', (value) => {
      value ? window.lenis?.stop() : window.lenis?.start();

      if (value) {
        this.$nextTick(() => {
          this.$refs.sideCartCloseButton?.focus();
        });
      } else if (this.lastCartTrigger?.isConnected) {
        this.$nextTick(() => {
          this.lastCartTrigger.focus();
          this.lastCartTrigger = null;
        });
      }
    });

    window.addEventListener('sobe:cart:item-added', (event) => {
      const detail = event.detail ?? {};
      const sideCartEnabled = getThemeCartParams().sideCartEnabled ?? true;

      if (sideCartEnabled) {
        this.openCart(event);
        this.announceCart(getCartOpenedText());
      } else {
        const toasts = detail.toasts?.length
          ? detail.toasts
          : [{ type: 'success', message: getAddedToCartText() }];

        toasts.forEach((toast) => Alpine.store('toastManager').add(toast));
      }

      if (detail.source === 'store-api' && typeof jQuery !== 'undefined') {
        jQuery(document.body).trigger('wc_fragment_refresh');
      }

      window.showSiteHeader?.();
    });

    const url = new URL(window.location.href);
    if (url.searchParams.get('sobe_open_cart') === '1') {
      dispatchCartItemAdded({
        source: 'redirect',
        trigger: document.activeElement,
      });
      url.searchParams.delete('sobe_open_cart');
      window.history.replaceState({}, '', url);
    }

    if (typeof jQuery !== 'undefined') {
      jQuery(document.body).on(
        'added_to_cart',
        (_event, fragments, cartHash, button) => {
          dispatchCartItemAdded({
            source: 'native-ajax',
            trigger: button || document.activeElement,
            fragments,
            cartHash,
            toasts: parseToastFragmentData(fragments),
          });
        },
      );

      jQuery(document.body).on('removed_from_cart', () => {
        lenis?.start();
        initAnimationBus();
        gsap.delayedCall(0.15, () => ScrollTrigger.refresh());
      });

      jQuery(document.body).on('added_to_cart updated_wc_div', () => {
        initAnimationBus();
        gsap.delayedCall(0.1, () => ScrollTrigger.refresh());
      });
    }
  },

  openCart(event = null) {
    const trigger = event?.detail?.trigger;
    if (trigger instanceof HTMLElement) {
      this.lastCartTrigger = trigger;
    } else if (document.activeElement instanceof HTMLElement) {
      this.lastCartTrigger = document.activeElement;
    }

    this.cartOpen = true;
  },

  closeCart() {
    this.cartOpen = false;
  },

  toggleCart(event = null) {
    if (this.cartOpen) {
      this.closeCart();
    } else {
      this.openCart(event);
    }
  },

  announceCart(message) {
    this.cartAnnouncement = '';

    window.setTimeout(() => {
      this.cartAnnouncement = message;
    }, 40);
  },

  async fetchFreshCart() {
    const resp = await fetch(getStoreApiCartUrl(), {
      headers: { Nonce: getStoreApiNonce() },
      credentials: 'same-origin',
    });

    if (!resp.ok) {
      return { items: [], count: 0 };
    }

    const cart = await resp.json();

    return {
      items: cart.items || [],
      count: getCartCount(cart),
    };
  },

  async updateCartQty(itemKey, quantity) {
    const nonce = getStoreApiNonce();
    const url = `/wp-json/wc/store/v1/cart/items/${encodeURIComponent(itemKey)}`;
    const method = quantity < 1 ? 'DELETE' : 'PUT';
    const opts = {
      method,
      credentials: 'same-origin',
      headers: { Nonce: nonce, 'Content-Type': 'application/json' },
    };

    if (method === 'PUT') opts.body = JSON.stringify({ quantity });

    try {
      const response = await fetch(url, opts);
      if (response.ok || response.status === 200) {
        const result = await this.fetchFreshCart();
        window.dispatchEvent(
          new CustomEvent('cart-updated', {
            detail: { items: result.items, count: result.count },
          }),
        );
      }
    } finally {
      if (typeof jQuery !== 'undefined') {
        jQuery(document.body).trigger('wc_fragment_refresh');
      }
    }
  },

  async removeFromCart(itemKey) {
    const nonce = getStoreApiNonce();

    try {
      const response = await fetch(
        `/wp-json/wc/store/v1/cart/items/${encodeURIComponent(itemKey)}`,
        {
          method: 'DELETE',
          credentials: 'same-origin',
          headers: { Nonce: nonce },
        },
      );

      if (response.ok || response.status === 200) {
        const result = await this.fetchFreshCart();
        window.dispatchEvent(
          new CustomEvent('cart-updated', {
            detail: { items: result.items, count: result.count },
          }),
        );
      }
    } finally {
      if (typeof jQuery !== 'undefined') {
        jQuery(document.body).trigger('wc_fragment_refresh');
      }
    }
  },

  toggleDark() {
    this.dark = !this.dark;
    localStorage.setItem('theme', this.dark ? 'dark' : 'light');
  },
}));

Alpine.plugin(focus);
window.Alpine = Alpine;
Alpine.start();

gsap.matchMedia().add('(prefers-reduced-motion: no-preference)', () => {
  const smoothScrollMobile = document.body.dataset.smoothScrollMobile;
  const isPointerFine = window.matchMedia('(pointer: fine)').matches;

  if (smoothScrollMobile !== 'true' && !isPointerFine) return;

  lenis = new Lenis({
    duration: 1.2,
    easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
  });
  window.lenis = lenis;

  gsap.ticker.add((time) => {
    lenis.raf(time * 1000);
  });
  gsap.ticker.lagSmoothing(0);
});

const scheduleIdle = (fn) =>
  'requestIdleCallback' in window
    ? requestIdleCallback(fn, { timeout: 2000 })
    : setTimeout(fn, 100);

initAnimationBus();

scheduleIdle(() => {
  initStickyHeader();
});

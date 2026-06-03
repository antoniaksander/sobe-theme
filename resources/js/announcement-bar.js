import { registerReinit } from './sobe-reinit.js';

const SELECTOR = '[data-announcement-bar]';
const MESSAGE_SELECTOR = '[data-announcement-bar-text]';
const MESSAGE_DATA_SELECTOR = '[data-announcement-bar-messages]';
const DISMISS_SELECTOR = '[data-announcement-bar-dismiss]';
const DISMISSED_KEY = 'sobe-bar-dismissed';
const ROTATE_INTERVAL = 4000;
const FADE_DURATION = 350;

const instances = new WeakMap();
const elements = new Set();

function getBars(root = document) {
  const bars = [...(root.querySelectorAll?.(SELECTOR) || [])];

  if (root.nodeType === Node.ELEMENT_NODE && root.matches(SELECTOR)) {
    bars.unshift(root);
  }

  return bars;
}

function readMessages(bar) {
  const script = bar.querySelector(MESSAGE_DATA_SELECTOR);

  if (!script) {
    return [];
  }

  try {
    const messages = JSON.parse(script.textContent || '[]');
    return Array.isArray(messages)
      ? messages.filter((message) => typeof message === 'string' && message.trim() !== '')
      : [];
  } catch (error) {
    console.warn('[announcement-bar] Could not parse messages.', error);
    return [];
  }
}

function setBarHeight(height) {
  document.documentElement.style.setProperty('--bar-h', `${height}px`);
}

function getDismissed() {
  try {
    return sessionStorage.getItem(DISMISSED_KEY) === '1';
  } catch (_error) {
    return false;
  }
}

function setDismissed() {
  try {
    sessionStorage.setItem(DISMISSED_KEY, '1');
  } catch (_error) {
    // Dismissal persists when storage is available; visibility updates either way.
  }
}

function updateScrollState(bar, state) {
  const atTop = window.scrollY < 2;

  if (state.atTop === atTop && state.ready) {
    return;
  }

  state.atTop = atTop;
  bar.classList.toggle('announcement-bar--scrolled', !atTop);
  setBarHeight(atTop ? state.barHeight : 0);
}

function destroyBar(bar) {
  const state = instances.get(bar);

  if (!state) {
    return;
  }

  state.controller.abort();
  clearInterval(state.rotateTimer);
  clearTimeout(state.fadeTimer);
  cancelAnimationFrame(state.readyFrame);

  instances.delete(bar);
  elements.delete(bar);
}

function dismissBar(bar) {
  setDismissed();
  bar.hidden = true;
  bar.classList.remove('announcement-bar--scrolled');
  setBarHeight(0);
  destroyBar(bar);
}

function rotateMessage(bar, state) {
  const text = bar.querySelector(MESSAGE_SELECTOR);

  if (!text || state.messages.length < 2) {
    return;
  }

  text.classList.add('opacity-0');

  state.fadeTimer = window.setTimeout(() => {
    state.current = (state.current + 1) % state.messages.length;
    text.textContent = state.messages[state.current];
    text.classList.remove('opacity-0');
  }, FADE_DURATION);
}

function initBar(bar) {
  if (instances.has(bar)) {
    return;
  }

  const messages = readMessages(bar);

  if (messages.length === 0) {
    bar.hidden = true;
    setBarHeight(0);
    return;
  }

  const isMultiple = messages.length > 1;

  if (!isMultiple && getDismissed()) {
    bar.hidden = true;
    setBarHeight(0);
    return;
  }

  const controller = new AbortController();
  const state = {
    atTop: null,
    barHeight: 0,
    controller,
    current: 0,
    fadeTimer: null,
    messages,
    ready: false,
    readyFrame: null,
    rotateTimer: null,
  };

  instances.set(bar, state);
  elements.add(bar);

  const text = bar.querySelector(MESSAGE_SELECTOR);
  if (text) {
    text.textContent = messages[state.current];
  }

  const dismiss = bar.querySelector(DISMISS_SELECTOR);
  dismiss?.addEventListener('click', () => dismissBar(bar), {
    signal: controller.signal,
  });

  window.addEventListener('scroll', () => updateScrollState(bar, state), {
    passive: true,
    signal: controller.signal,
  });

  if (isMultiple) {
    state.rotateTimer = window.setInterval(
      () => rotateMessage(bar, state),
      ROTATE_INTERVAL,
    );
  }

  bar.hidden = false;
  state.readyFrame = window.requestAnimationFrame(() => {
    state.barHeight = bar.offsetHeight;
    state.ready = true;
    updateScrollState(bar, state);
    bar.classList.add('announcement-bar--ready');
  });
}

export function init(root = document) {
  let bars = getBars(root);

  if (bars.length === 0 && root !== document) {
    bars = getBars(document);
  }

  bars.forEach(initBar);
}

export function destroy() {
  [...elements].forEach(destroyBar);
}

registerReinit('announcement-bar', { init, destroy });

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => init(document), {
    once: true,
  });
} else {
  init(document);
}

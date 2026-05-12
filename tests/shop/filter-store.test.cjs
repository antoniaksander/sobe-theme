/**
 * Unit tests for resources/js/filter-store.js
 *
 * The store is a module-level singleton. _reset() is called before each test
 * so state does not leak between cases.
 */

const store = require('../../resources/js/filter-store.js');

beforeEach(() => {
  store._reset();
});

// ── Initial state ─────────────────────────────────────────────────────────────

describe('initial state', () => {
  test('getState() returns null before any commit', () => {
    expect(store.getState()).toBeNull();
  });

  test('getAction() returns null before any commit', () => {
    expect(store.getAction()).toBeNull();
  });

  test('getNonce() returns null before any commit', () => {
    expect(store.getNonce()).toBeNull();
  });
});

// ── commit ────────────────────────────────────────────────────────────────────

describe('commit', () => {
  test('updates state, action, and nonce', () => {
    store.commit({ filter_color: ['blue'] }, 'sobe_filter_products', 'abc123');
    expect(store.getState()).toEqual({ filter_color: ['blue'] });
    expect(store.getAction()).toBe('sobe_filter_products');
    expect(store.getNonce()).toBe('abc123');
  });

  test('omitting action preserves the previous action', () => {
    store.commit({}, 'action1', 'nonce1');
    store.commit({ filter_color: ['red'] });
    expect(store.getAction()).toBe('action1');
  });

  test('omitting nonce preserves the previous nonce', () => {
    store.commit({}, 'action1', 'nonce1');
    store.commit({ filter_color: ['red'] });
    expect(store.getNonce()).toBe('nonce1');
  });

  test('second commit replaces state entirely', () => {
    store.commit({ filter_color: ['blue'] }, 'act', 'nnc');
    store.commit({ filter_size: ['xl'] }, 'act', 'nnc');
    expect(store.getState()).toEqual({ filter_size: ['xl'] });
    expect(store.getState()).not.toHaveProperty('filter_color');
  });
});

// ── subscribe ─────────────────────────────────────────────────────────────────

describe('subscribe', () => {
  test('subscriber receives state on commit', () => {
    const spy = jest.fn();
    store.subscribe(spy);
    store.commit({ filter_color: ['red'] });
    expect(spy).toHaveBeenCalledWith({ filter_color: ['red'] });
  });

  test('subscriber receives updated state on subsequent commits', () => {
    const spy = jest.fn();
    store.subscribe(spy);
    store.commit({ filter_color: ['red'] });
    store.commit({ filter_color: ['blue'] });
    expect(spy).toHaveBeenCalledTimes(2);
    expect(spy).toHaveBeenLastCalledWith({ filter_color: ['blue'] });
  });

  test('returns an unsubscribe function that stops notifications', () => {
    const spy = jest.fn();
    const unsub = store.subscribe(spy);
    unsub();
    store.commit({ filter_color: ['red'] });
    expect(spy).not.toHaveBeenCalled();
  });

  test('multiple subscribers all receive each commit', () => {
    const spy1 = jest.fn();
    const spy2 = jest.fn();
    store.subscribe(spy1);
    store.subscribe(spy2);
    store.commit({ min_price: '20' });
    expect(spy1).toHaveBeenCalledTimes(1);
    expect(spy2).toHaveBeenCalledTimes(1);
  });

  test('unsubscribing one does not affect the other', () => {
    const spy1 = jest.fn();
    const spy2 = jest.fn();
    store.subscribe(spy1);
    const unsub2 = store.subscribe(spy2);
    unsub2();
    store.commit({ filter_color: ['blue'] });
    expect(spy1).toHaveBeenCalledTimes(1);
    expect(spy2).not.toHaveBeenCalled();
  });
});

// ── _reset ────────────────────────────────────────────────────────────────────

describe('_reset', () => {
  test('clears state, action, and nonce', () => {
    store.commit({ filter_color: ['blue'] }, 'act', 'nnc');
    store._reset();
    expect(store.getState()).toBeNull();
    expect(store.getAction()).toBeNull();
    expect(store.getNonce()).toBeNull();
  });

  test('clears all subscribers so they are not called after reset', () => {
    const spy = jest.fn();
    store.subscribe(spy);
    store.commit({ filter_color: ['blue'] }, 'act', 'nnc');
    store._reset();
    store.commit({ filter_color: ['red'] });
    expect(spy).toHaveBeenCalledTimes(1);
  });
});

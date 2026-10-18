/**
 * @jest-environment jsdom
 *
 * Shared scroll-lock ref count (resources/js/scroll-lock.js).
 */

describe('scroll lock', () => {
  let lock;

  beforeEach(() => {
    jest.resetModules();
    window.lenis = { stop: jest.fn(), start: jest.fn() };
    lock = require('../../resources/js/scroll-lock.js');
  });

  afterEach(() => {
    delete window.lenis;
  });

  it('stops Lenis on the first lock and starts it after the last unlock', () => {
    lock.lockScroll();
    lock.lockScroll();
    expect(window.lenis.stop).toHaveBeenCalledTimes(1);
    expect(lock.isScrollLocked()).toBe(true);

    lock.unlockScroll();
    expect(window.lenis.start).not.toHaveBeenCalled();
    expect(lock.isScrollLocked()).toBe(true);

    lock.unlockScroll();
    expect(window.lenis.start).toHaveBeenCalledTimes(1);
    expect(lock.isScrollLocked()).toBe(false);
  });

  it('never goes below zero on an extra unlock', () => {
    lock.unlockScroll();
    lock.lockScroll();
    expect(lock.isScrollLocked()).toBe(true);
    lock.unlockScroll();
    expect(lock.isScrollLocked()).toBe(false);
  });
});

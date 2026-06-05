const { shouldIgnoreTransitionVisit } = require('../../resources/js/page-transition-url.js');

describe('page transition URL exclusions', () => {
  test('matches exact paths and child path segments', () => {
    const patterns = ['/cart', '/checkout', '/wp-json/'];

    expect(shouldIgnoreTransitionVisit('/cart', patterns)).toBe(true);
    expect(shouldIgnoreTransitionVisit('/cart/', patterns)).toBe(true);
    expect(shouldIgnoreTransitionVisit('/cart/estimate', patterns)).toBe(true);
    expect(shouldIgnoreTransitionVisit('/wp-json/wc/store', patterns)).toBe(true);
  });

  test('does not match partial path substrings', () => {
    const patterns = ['/cart', '/product/'];

    expect(shouldIgnoreTransitionVisit('/cartridges', patterns)).toBe(false);
    expect(shouldIgnoreTransitionVisit('/products', patterns)).toBe(false);
    expect(shouldIgnoreTransitionVisit('/productivity', patterns)).toBe(false);
  });

  test('matches query parameters by key and optional value', () => {
    expect(shouldIgnoreTransitionVisit('/shop?add-to-cart=123', ['add-to-cart='])).toBe(true);
    expect(shouldIgnoreTransitionVisit('/shop?add-to-cart=123', ['add-to-cart=456'])).toBe(false);
    expect(shouldIgnoreTransitionVisit('/shop?view=grid', ['add-to-cart='])).toBe(false);
  });

  test('matches URL strings with origins', () => {
    const patterns = ['/my-account'];

    expect(shouldIgnoreTransitionVisit('https://example.test/my-account/orders', patterns)).toBe(true);
    expect(shouldIgnoreTransitionVisit('https://example.test/accounting', patterns)).toBe(false);
  });
});

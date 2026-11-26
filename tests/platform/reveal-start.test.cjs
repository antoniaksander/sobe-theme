/**
 * Scroll-in animation start point (resources/js/reveal-start.js).
 */

const { revealStart } = require('../../resources/js/reveal-start.js');

const el = (top) => ({ getBoundingClientRect: () => ({ top }) });
const win = (innerHeight, scrollY = 0) => ({ innerHeight, scrollY });

describe('revealStart', () => {
  it('plays at once for a block already on screen at load', () => {
    expect(revealStart(el(1132), 'top 85%', win(1265))()).toBe('top bottom');
  });

  it('keeps the usual start for a block below the fold', () => {
    expect(revealStart(el(1400), 'top 85%', win(1265))()).toBe('top 85%');
    expect(revealStart(el(2000), 'top 80%', win(1265))()).toBe('top 80%');
  });

  it('measures from the top of the page, not the current scroll position', () => {
    // 300px scrolled: a block at viewport 900 sits at page 1200, inside the first screen.
    expect(revealStart(el(900), 'top 85%', win(1265, 300))()).toBe('top bottom');
    // 1000px scrolled: viewport 900 is page 1900, below the first screen.
    expect(revealStart(el(900), 'top 85%', win(1265, 1000))()).toBe('top 85%');
  });
});

/**
 * ScrollTrigger `start` for scroll-in animations.
 *
 * A block whose top is already on screen when the page loads plays right
 * away ("top bottom" is then already passed). Otherwise a block just under a
 * tall hero showed as a blank strip until the visitor scrolled far enough
 * to reach the usual "top 85%" point, which looked like a broken page.
 * Blocks further down keep the usual start, so they still animate in on scroll.
 *
 * Returned as a function so ScrollTrigger re-evaluates it on refresh.
 */
export function revealStart(el, fallback = 'top 85%', win = window) {
  return () => {
    const top = el.getBoundingClientRect().top + (win.scrollY || 0);

    return top < win.innerHeight ? 'top bottom' : fallback;
  };
}

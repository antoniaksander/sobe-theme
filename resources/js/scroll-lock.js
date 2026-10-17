// Shared scroll-lock ref count. The side cart, a mobile nav, a modal and
// similar overlays can each independently want Lenis paused, and can be open
// at the same time. A plain stop()/start() per component races: closing one
// while another is still open resumes Lenis prematurely. Only stop on the
// first lock, only start once every lock is released.
let lockCount = 0;

export function lockScroll() {
  lockCount += 1;
  if (lockCount === 1) {
    window.lenis?.stop();
  }
}

export function isScrollLocked() {
  return lockCount > 0;
}

export function unlockScroll() {
  lockCount = Math.max(0, lockCount - 1);
  if (lockCount === 0) {
    window.lenis?.start();
  }
}

/**
 * Unidirectional filter store — single authoritative source of truth for active
 * filter state, the AJAX action name, and the AJAX nonce.
 *
 * Both catalog-filters/view.js (writer) and shop-load-more.js (reader) import
 * this module. Because both are separate Vite entry points that share the same
 * import, Vite emits a single shared chunk — guaranteeing one instance at runtime.
 *
 * API
 *   commit(state, action?, nonce?) — update state; notify all subscribers
 *   getState()  → current filter state object | null
 *   getAction() → current AJAX action string | null
 *   getNonce()  → current AJAX nonce string | null
 *   subscribe(fn) → returns unsubscribe function
 *   reset()     → clears all state without notifying
 *   _reset()    → test-only teardown; clears all state without notifying
 */

let _state  = null;
let _action = null;
let _nonce  = null;
const _subs = new Set();

export function commit(state, action, nonce) {
  _state = state;
  if (action !== undefined) _action = action;
  if (nonce  !== undefined) _nonce  = nonce;
  _subs.forEach((fn) => fn(_state));
}

export function getState()  { return _state;  }
export function getAction() { return _action; }
export function getNonce()  { return _nonce;  }

export function subscribe(fn) {
  _subs.add(fn);
  return () => _subs.delete(fn);
}

export function reset() {
  _state  = null;
  _action = null;
  _nonce  = null;
  _subs.clear();
}

export function _reset() {
  reset();
}

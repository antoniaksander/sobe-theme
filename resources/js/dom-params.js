/**
 * Helpers for reading per-page params from a DOM script tag inside the page
 * transitions container.
 *
 * PHP emits <script type='application/json' data-sobe-params='{module}'>{...}</script>
 * inside #main (or the configured container) so params are replaced on each
 * navigation. Modules read with these helpers and verify contextUrl matches
 * the current page to detect stale state.
 *
 * Exports: findParamScript, readParams, isCurrentContext.
 */

/**
 * Find a params script in a root node.
 *
 * @param {Document|Element} root
 * @param {string} type
 * @returns {HTMLScriptElement|null}
 */
export function findParamScript(root, type) {
  if (!root || typeof type !== 'string' || type === '') return null;

  const selector = 'script[type="application/json"][data-sobe-params]';
  const isMatchingScript = (node) => (
    typeof node?.matches === 'function'
    && node.matches(selector)
    && node.dataset?.sobeParams === type
  );

  if (isMatchingScript(root)) {
    return root;
  }

  if (typeof root.querySelectorAll !== 'function') {
    return null;
  }

  return Array.from(root.querySelectorAll(selector))
    .find((script) => script.dataset.sobeParams === type) || null;
}

/**
 * Read and parse params from a params script.
 *
 * @param {Document|Element} root
 * @param {string} type
 * @param {*} fallback
 * @returns {*}
 */
export function readParams(root, type, fallback = null) {
  const script = findParamScript(root, type);
  if (!script) return fallback;

  try {
    return JSON.parse(script.textContent || '');
  } catch (error) {
    console.warn(`[sobe dom-params] Failed to parse params for "${type}".`, error);
    return fallback;
  }
}

/**
 * Check that params belong to the current browser pathname.
 *
 * @param {object|null} params
 * @param {string} label
 * @returns {boolean}
 */
export function isCurrentContext(params, label) {
  if (!params?.contextUrl) return true;

  try {
    const contextUrl = new URL(params.contextUrl, window.location.href);

    if (contextUrl.pathname === window.location.pathname) {
      return true;
    }

    console.warn(`[sobe ${label}] Ignoring stale params for "${contextUrl.pathname}" on "${window.location.pathname}".`);
    return false;
  } catch (error) {
    console.warn(`[sobe ${label}] Ignoring params with an invalid contextUrl.`, error);
    return false;
  }
}

/**
 * Module lifecycle registry.
 *
 * Used to coordinate init/destroy of frontend modules across page navigations
 * or AJAX content replacement. Modules register via registerReinit; the page
 * transition layer calls destroyPage on visit start and initPage on page view.
 *
 * Exports: registerReinit, destroyPage, initPage, markPageMounted.
 */

const modules = new Map();
let mounted = true;

/**
 * Register a module's lifecycle handlers.
 *
 * @param {string} name
 * @param {{ init: Function, destroy?: Function }} handlers
 * @returns {void}
 */
export function registerReinit(name, { init, destroy } = {}) {
  if (typeof name !== 'string' || name.trim() === '') {
    throw new TypeError('[sobe-reinit] registerReinit requires a non-empty module name.');
  }

  if (typeof init !== 'function') {
    throw new TypeError(`[sobe-reinit] registerReinit("${name}") requires an init function.`);
  }

  if (destroy !== undefined && typeof destroy !== 'function') {
    throw new TypeError(`[sobe-reinit] registerReinit("${name}") destroy must be a function when provided.`);
  }

  modules.set(name, { init, destroy });
}

/**
 * Destroy all registered page modules once for the current mounted page.
 *
 * @returns {void}
 */
export function destroyPage() {
  if (!mounted) return;

  mounted = false;

  for (const [name, { destroy }] of modules) {
    if (typeof destroy !== 'function') continue;

    try {
      destroy();
    } catch (error) {
      console.error(`[sobe-reinit] Error destroying module "${name}".`, error);
    }
  }
}

/**
 * Initialize all registered page modules for the provided DOM root.
 *
 * @param {Document|Element} root
 * @returns {void}
 */
export function initPage(root = document) {
  for (const [name, { init }] of modules) {
    try {
      init(root);
    } catch (error) {
      console.error(`[sobe-reinit] Error initializing module "${name}".`, error);
    }
  }

  mounted = true;
}

/**
 * Mark the current page as mounted after an aborted transition.
 *
 * @returns {void}
 */
export function markPageMounted() {
  mounted = true;
}

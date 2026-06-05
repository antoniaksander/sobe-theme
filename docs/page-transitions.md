# Page Transitions

Page transitions are an opt-in subsystem for client-side navigation using
Swup. The engine is integrated with the theme lifecycle registry so frontend
modules can clean up before a page is replaced and initialize again after new
content enters the DOM.

The subsystem is disabled by default. Forks opt in with a config flag, then
customize behavior through PHP filters and lifecycle-safe modules.

## Enabling Page Transitions

Enable the engine in `config/theme.php`:

```php
'page_transitions' => [
    'enabled' => true,
    'container_selector' => '#main',
],
```

Then rebuild assets:

```bash
npm run build
```

Verify a rendered frontend page:

- `window.sobePageTransitionsConfig` exists before the engine script.
- `window.sobeSwup` exists after the page loads.
- The script tag for `resources/js/sobe-page-transitions.js` is present only
  when the flag is enabled.

## Architecture

The system has three layers:

- Engine: `resources/js/sobe-page-transitions.js` boots Swup, excludes unsafe
  routes, updates body classes, and calls the lifecycle registry at transition
  boundaries.
- Lifecycle registry: `resources/js/sobe-reinit.js` coordinates module
  `init` and `destroy` handlers across page swaps and AJAX content replacement.
- Strategy C params: `resources/js/dom-params.js` reads DOM-scoped, per-page
  data emitted by PHP and protects modules from stale state with `contextUrl`
  checks.

On `visit:start`, the engine dispatches `sobe:shell-reset` and calls
`destroyPage()`. On `page:view`, it calls `initPage(container)`, where
`container` is the configured page-transition container, usually `#main`.
If a visit is aborted or fetching the next page fails, the engine re-initializes
the current container so modules destroyed at `visit:start` are restored.

When transitions are enabled, the PHP wiring also enqueues registered block
`view.js` module handles up front. WordPress normally emits those footer scripts
only for blocks rendered in the current request; preloading the registered view
modules prevents destination-page blocks from arriving after a Swup swap without
their runtime code.

Modules register with:

```js
registerReinit('module-name', { init, destroy });
```

Once registered, the module does not need to know whether initialization is
coming from a hard navigation, a Swup transition, or an AJAX content refresh.

## Module Authoring Guide

Use `resources/blocks/sobe/reviews-slider/view.js` as the canonical full
example. It scopes queries to the provided root, stores instance state in a
`WeakMap`, uses `AbortController` for listeners, clears timers on destroy, and
keeps a `DOMContentLoaded` fallback for hard navigations.

Minimal lifecycle-safe module shape:

```js
import { registerReinit } from '../../js/sobe-reinit.js';

const instances = new WeakMap();
const selector = '.example-module';

function getRoots(root = document) {
  const elements = [...(root.querySelectorAll?.(selector) || [])];

  if (root.nodeType === Node.ELEMENT_NODE && root.matches(selector)) {
    elements.unshift(root);
  }

  return elements;
}

function init(root = document) {
  getRoots(root).forEach((element) => {
    if (instances.has(element)) return;

    const controller = new AbortController();
    const { signal } = controller;
    const button = element.querySelector('[data-example-button]');
    const timerId = window.setInterval(() => {
      // Element-scoped work.
    }, 1000);

    button?.addEventListener('click', () => {
      element.classList.toggle('is-active');
    }, { signal });

    instances.set(element, {
      controller,
      timerId,
    });
  });
}

function destroy() {
  document.querySelectorAll(selector).forEach((element) => {
    const state = instances.get(element);
    if (!state) return;

    state.controller.abort();
    window.clearInterval(state.timerId);

    // Restore any moved DOM here before deleting state.

    instances.delete(element);
  });
}

registerReinit('example-module', { init, destroy });

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => init(document));
} else {
  init(document);
}

export { init, destroy };
```

Each piece exists for a specific reason:

- `WeakMap` keyed by the module root prevents double initialization.
- `AbortController` makes teardown of element-scoped listeners explicit and
  complete.
- Root-scoped queries allow partial re-initialization after AJAX content
  replacement.
- `DOMContentLoaded` fallback keeps hard-navigation behavior unchanged when
  page transitions are disabled.
- Named `init` and `destroy` exports make the lifecycle contract clear and easy
  to test.

## Strategy C: Page-Local PHP Params

Modules that need per-page data from PHP should read DOM-scoped params from
inside the swappable container. This keeps incoming page data tied to the
incoming page, rather than to a long-lived `window` global.

PHP emits JSON:

```php
<?php

$params = [
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('example_action'),
    'contextUrl' => \App\sobe_current_request_url(),
];
?>

<script type="application/json" data-sobe-params="example-module">
  {!! wp_json_encode($params, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
```

JS reads and verifies the params:

```js
import { readParams, isCurrentContext } from '../../js/dom-params.js';

function init(root = document) {
  const params = readParams(root, 'example-module', window.sobeExampleParams);

  if (!params || !isCurrentContext(params, 'example-module')) {
    return;
  }

  // Use params.ajaxUrl, params.nonce, and other page-local data.
}
```

Every payload must include `contextUrl`, computed with
`App\sobe_current_request_url()`. In AJAX context, the request URI is
`admin-ajax.php`, which is not useful for stale detection. Do not inline the
`wp_doing_ajax()` check in each module; use the shared helper.

Window globals may remain as a legacy fallback for non-transition page loads,
but new lifecycle-safe modules should prefer DOM params.

## Animation Lifecycle

Page-content animations are wrapped in a `gsap.context()` scoped to the page
root. Destroying the page reverts that context, which removes ScrollTriggers
and animation state owned by the old page.

Persistent shell animations live outside the page context. The sticky header is
the model for this: it belongs to the shell, not the swappable page content, so
it survives transitions.

`initAnimationBus()` is additive on re-call. It adds newly discovered animated
elements to the existing page context instead of tearing down the whole
animation layer. Explicit `destroy` is the lifecycle teardown path.

## Body Class Handling

On transition, the incoming page's body classes win. Runtime classes that must
survive are preserved separately.

The default preserve list is:

- `admin-bar`
- `logged-in`

The merge also handles the mutually exclusive `customize-support` /
`no-customize-support` pair. These are runtime feature flags. Treat them as a
pair and prefer the live runtime value when the incoming page has neither.

Forks can customize exact classes with:

```php
add_filter('sobe/page_transitions/preserve_body_classes', function (array $classes): array {
    $classes[] = 'my-runtime-class';
    return $classes;
});
```

## Excluded Routes

The engine excludes these URL patterns by default:

- `/cart`
- `/checkout`
- `/my-account`
- `/product/`
- `/wp-admin`
- `/wp-login.php`
- `wp-admin/admin-ajax.php`
- `/wp-json/`
- `add-to-cart=`

Path exclusions match exact paths and child path segments. For example, `/cart`
matches `/cart` and `/cart/shipping`, but not `/cartridges`. Query exclusions
with `=` match query parameters by key, and by value when a value is supplied.

Customize the list with:

```php
add_filter('sobe/page_transitions/excluded_urls', function (array $patterns): array {
    $patterns[] = '/members-only/';
    return $patterns;
});
```

Logged-in users are excluded by default because WordPress admin-bar contextual
links can become stale when only `#main` is replaced. Forks may opt in after
validating their admin-bar behavior:

```php
add_filter('sobe/page_transitions/allow_logged_in', '__return_true');
```

Product detail pages are excluded by default. Enabling product detail
transitions requires more work than removing `/product/` from this list.
WooCommerce conditionally enqueues `wc-add-to-cart-variation` only when
`is_product()` is true on the current request. Forks that include product
detail pages in Swup transitions need to force-enqueue any required
WooCommerce scripts globally or through an equivalent fork-owned strategy.

## Public PHP Filters

| Hook | Type | Parameters | Return | Default |
|------|------|------------|--------|---------|
| `sobe/page_transitions/enabled` | filter | `bool $enabled` | `bool` | `true`. Final per-request runtime switch. The config flag has already gated the subsystem before this filter runs. |
| `sobe/page_transitions/excluded_urls` | filter | `array $patterns` | `array` | URL pattern strings listed in [Excluded Routes](#excluded-routes). |
| `sobe/page_transitions/container_selector` | filter | `string $selector` | `string` | `#main` |
| `sobe/page_transitions/preserve_body_classes` | filter | `array $classes` | `array` | `['admin-bar', 'logged-in']` |
| `sobe/page_transitions/allow_logged_in` | filter | `bool $allow` | `bool` | `false`. Logged-in users are excluded unless a fork opts in. |
| `sobe/page_transitions/enqueue_block_view_scripts` | filter | `bool $enqueue` | `bool` | `true`. Enqueues all registered block `view.js` handles while transitions are active. |

Example:

```php
add_filter('sobe/page_transitions/enabled', function (bool $enabled): bool {
    return is_preview() ? false : $enabled;
});
```

## Shell Reset Event

The engine dispatches `sobe:shell-reset` on `document` at `visit:start`.
Persistent shell components should listen for it and close open UI before the
page content is replaced.

```js
document.addEventListener('sobe:shell-reset', () => {
  window.dispatchEvent(new CustomEvent('close-search'));
  window.dispatchEvent(new CustomEvent('close-mobile-menu'));
});
```

If the listener belongs to a lifecycle module, bind it with an
`AbortController` and abort it in `destroy()`. For persistent shell components,
a once-at-boot listener is fine.

## WooCommerce Considerations

`catalog-filters` and `shop-load-more` are lifecycle-safe and survive
transitions. They read page-local params, clean up moved DOM, abort fetches,
disconnect observers, and re-initialize from the incoming page root.

`wc-cart-fragments` runs once at boot. The side-cart Alpine state belongs to
the persistent shell, so cart drawer state can carry across page transitions.
Shell components that should close on navigation should listen for
`sobe:shell-reset`.

WooCommerce native scripts such as `wc-add-to-cart` and
`wc-add-to-cart-variation` are conditionally enqueued per page. If a fork
includes WooCommerce pages that are excluded by default, force-enqueue
requirements apply. See [Excluded Routes](#excluded-routes).

## Debugging

When the engine is active, `window.sobeSwup` exists after page load. Inspect it
in the browser console to confirm Swup booted.

For detailed transition logs, use Swup's own debug tooling in the fork.

Common symptoms:

| Symptom | Likely cause |
|---------|--------------|
| Animations do not fire after transition. | The animation module is not registered with `registerReinit`, or old `data-animated` marks are blocking re-fire. Lifecycle-safe animation code should reset marks before re-initializing. |
| Module stops working after a failed navigation. | The engine should re-run `initPage()` on `fetch:error`, `fetch:timeout`, and `visit:abort`. Check the console for module init errors. |
| Module state leaks across pages. | `destroy()` is not cleaning up all state. Check `AbortController` usage, observers, timers, third-party instances, and moved DOM. |
| Console warning about stale context. | Strategy C `contextUrl` does not match the current page. The module skips re-init rather than running with stale data. Check where PHP emits the params and make sure it uses `App\sobe_current_request_url()`. |

## History API Integration

When transitions are enabled, modules that update browser history via
`pushState` or `replaceState` should preserve Swup's history state structure.
Swup's `skipPopStateHandling` default ignores popstate events whose
`state.source` is not `'swup'`, so unmarked history entries break
back-navigation.

When `window.sobeSwup` is defined, write history entries like this:

```js
const targetUrl = '/some/url';
const swupState = window.sobeSwup
  ? { ...(history.state ?? {}), source: 'swup', url: targetUrl }
  : (history.state ?? {});
history.pushState(swupState, '', targetUrl);
```

This pattern is already applied in the bundled `catalog-filters` block when
emitting filtered URLs. Forks that introduce new modules with their own
client-side URL updates should follow the same pattern.

## Off-By-Default Rationale

Page transitions are a meaningful UX and integration change. Forks pulling
upstream updates should not get transitions surprise-enabled during a routine
sync.

The default is off so each fork can opt in, validate its blocks, WooCommerce
paths, analytics, and shell UI, then customize the public filters where needed.

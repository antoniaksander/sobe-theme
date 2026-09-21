# Client vs Platform Boundary

| File/Area | Platform Owns | Client Owns |
|-----------|----------------|--------------|
| `config/theme.php` | Default `sobe` prefix, `sobe` textdomain, neutral config defaults | Client prefix, `client_modules`, `disabled_modules`, `block_categories`, and client-specific config values. This is the primary fork-owned file. |
| `resources/css/tokens.css` | Token names, scales, neutral defaults, dark-mode contract | No client edits. Brand colors and optional brand font token overrides belong in `resources/css/client-tokens.css`. |
| `resources/js/app.js` | Alpine app shell, event names, dark mode, nav, search, side-cart, toasts | Client scripts that consume documented events |
| `resources/js/sobe-reinit.js`, `resources/js/dom-params.js`, `resources/js/body-class-merge.js` | Lifecycle registry, page-local params reader, and body-class merge infrastructure | No client overrides. Extend through `registerReinit`, Strategy C params, and documented PHP filters. |
| `resources/js/sobe-page-transitions.js` | Swup engine and lifecycle integration contract | Filter-based customization through `sobe/page_transitions/*`; fork-owned validation before enabling transitions. |
| `resources/views/layouts/app.blade.php` | Platform layout shell, app wrapper, SEO baseline, overlays | Rare structural overrides in client repo |
| `resources/blocks/sobe/*` | Universal production blocks and examples | Client copies under client namespace |
| `functions.php` | The platform module list and the bootstrap. No client edits. | Fork-owned modules through `config/theme.php` `client_modules`; a replaced platform module through `disabled_modules`. |
| `app/blocks.php` | Manifest registration, platform categories, allowlist hook. No client edits. | Client manifest entries for client blocks; client editor categories through `config/theme.php` `block_categories`. |
| `app/setup-customizer.php` | Generic platform settings | Client-specific settings in client repo |
| `app/setup-patterns.php` | Hidden layout patterns, `product_brand` registration contract | Client pattern additions |
| `app/setup-search.php` | Search endpoint, params, result hooks | Search result tuning via hooks |
| `app/woocommerce*.php` | Shared WC catalog, PDP, filters, side-cart, hook contracts | Hook callbacks and rare template overrides |
| `resources/views/woocommerce/` | Platform WC templates | Client overrides only for structural changes |
| `resources/views/components/` | Generic components and WC/search shells | Client-only components |
| `resources/patterns/` | Hidden platform layout patterns | Client-owned patterns in client repo |
| `resources/config/core-allowed-blocks.json` | Core/WC allowlist baseline | Client extensions through hook/config |

## Rule

If a platform file needs client-specific markup, copy the relevant block or view into the client repo and wire it through hooks or client namespace registration. Do not edit upstream `sobe/*` files in place for a client.

The lifecycle subsystem is platform infrastructure. Forks adding new blocks or
frontend modules should follow the lifecycle pattern in
[page-transitions.md](page-transitions.md#module-authoring-guide) instead of
overriding `sobe-reinit.js`, `dom-params.js`, or `body-class-merge.js`.

The Swup engine is also platform-owned. Forks customize route exclusions,
container selectors, runtime enablement, and body-class preservation through
the public `sobe/page_transitions/*` PHP filters. Do not override
`sobe-page-transitions.js` for routine fork behavior.

A fork that needs a different transition engine would need to implement the
same lifecycle contract: call `destroyPage()` when leaving the current page and
`initPage(root)` after the new page content is mounted. That is an advanced
extension and is not a documented public extension point in the initial
release.

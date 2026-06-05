# Changelog

## Unreleased

### Added

- Baseline `Article` and `BreadcrumbList` structured data (JSON-LD) for regular
  posts and pages (`app/seo.php`). WooCommerce already provides Product and
  BreadcrumbList schema on shop pages, so this covers only non-WooCommerce
  content to avoid duplicate markup. Skipped when a dedicated SEO plugin is
  active; extend via the `sobe/seo/extra_schema` filter.

## v2.5.0 - 2026-06-05

### Added

- `make:block` scaffolder is now namespace-agnostic: it accepts any category
  slug and emits neutral-root-class + namespace-modifier templates, so client
  forks can scaffold blocks in their own namespace.

### Changed

- **Breaking (forks):** the catalog config key `theme.wc_columns.*` was renamed
  to `theme.product_catalog.*` (and gained a `per_page` value) so Customizer
  defaults and catalog readers share a single source of truth. **Forks must
  rename this key in their own `config/theme.php` when syncing** — otherwise
  catalog column/per-page defaults silently fall back to hard-coded values.
- `sobe/example` is no longer registered by default; registration is gated
  behind `config('theme.blocks.register_example')` (default `false`). The
  manifest entry is retained on purpose — forks should keep it rather than
  deleting it to hide the block.

### Fixed

- Reveal animations gated by `ScrollTrigger` not firing on browser
  back-navigation. The page transitions engine now refreshes `ScrollTrigger`
  after `page:view`, deferred until scroll restoration completes.
- `catalog-filters` block losing Swup history integration when emitting
  filtered URLs. `pushState` calls now preserve Swup's history state structure
  (`source: 'swup'`) when `window.sobeSwup` is defined, so back-navigation over
  filter URLs triggers a Swup visit instead of being ignored.
- Swiper base CSS now loads through the global app stylesheet so Swiper-based
  UI remains styled on non-WooCommerce pages.
- Page transitions now preload block view scripts, re-initialize the current
  page after failed or aborted visits, skip logged-in users by default to avoid
  stale admin-bar links, and match excluded routes by path/query boundaries.

## v2.4.0 - 2026-06-02

### Breaking

- None yet.

### Added

- Added module lifecycle registry (`resources/js/sobe-reinit.js`) for
  coordinating init/destroy of frontend modules across page navigations or AJAX
  content replacement. Modules register via
  `registerReinit(name, { init, destroy })`; the page lifecycle calls
  `destroyPage()` and `initPage(root)` at the appropriate moments.
- Added DOM-scoped page params helper (`resources/js/dom-params.js`)
  implementing a script-tag-based pattern: PHP emits per-page params as JSON
  script tags
  (`<script type="application/json" data-sobe-params="{module}">`) inside the
  swappable container; JS reads with `findParamScript`, `readParams`, and
  `isCurrentContext` helpers. Includes `contextUrl` stale detection.
- Added body class merge helper (`resources/js/body-class-merge.js`) for
  merging incoming page body classes while preserving runtime classes. Handles
  `admin-bar`, `logged-in`, and the mutually exclusive `customize-support` /
  `no-customize-support` pair.
- Added shared `App\sobe_current_request_url()` PHP helper (in
  `app/helpers.php`) that returns the front-end URL even in AJAX context (uses
  `wp_get_referer()` when `wp_doing_ajax()`).
- Added Swup page transitions engine (`app/page-transitions.php`,
  `resources/js/sobe-page-transitions.js`). It is disabled by default, opt-in
  via the `config/theme.php` `page_transitions.enabled` flag, and integrates
  with the lifecycle registry.
- Added public page transition filters:
  `sobe/page_transitions/enabled`,
  `sobe/page_transitions/excluded_urls`,
  `sobe/page_transitions/container_selector`, and
  `sobe/page_transitions/preserve_body_classes`.
- Added `sobe:shell-reset` custom event dispatched on `visit:start` so
  persistent shell components such as mobile nav, search overlay, and cart
  drawer can reset open state on navigation.
- Added `docs/page-transitions.md` documenting the lifecycle subsystem, module
  authoring guide, Strategy C params pattern, public filters, debugging, and
  off-by-default rationale.

### Changed

- Refactored five block view scripts (`faq`, `product-carousel`,
  `reviews-slider`, `our-brands`, `product-categories-grid`) to a lifecycle
  pattern with named `init`/`destroy` exports, WeakMap-keyed instance tracking,
  and `AbortController` for element-scoped listeners. `DOMContentLoaded`
  fallback retained for initial page load.
- Scoped page-content animations in `resources/js/animations.js` to
  `gsap.context()` with explicit destroy via `context.revert()`. Persistent
  shell animations (sticky header) remain outside the context and survive page
  transitions. `initAnimationBus()` is now additive on re-call rather than
  destructive.
- Refactored `resources/blocks/sobe/catalog-filters/view.js` and
  `resources/js/shop-load-more.js` to the lifecycle pattern with full teardown:
  moved-DOM restoration, fetch abort via `AbortController`, observer
  disconnect, `noUiSlider` destroy, `gsap.context` revert, debounce timer
  cleanup, and filter-store reset. Page-local params now emitted as DOM JSON
  script tags inside the swappable container in addition to existing window
  globals (legacy fallback retained for backward compatibility).
- Updated `docs/client-fork-guide.md` with a brief section on the lifecycle
  subsystem.
- Updated `docs/client-boundary.md` to document the lifecycle subsystem as
  upstream infrastructure.

### Fixed

- None yet.

## v2.3.0 - 2026-05-21

### Added

- Added conditional WooCommerce stylesheet enqueueing for shortcode and curated
  WooCommerce block contexts while preserving the existing archive, cart,
  checkout, account, and `sobe/product-carousel` conditions.
- Allowed native WooCommerce block availability in the editor, including 171
  WooCommerce blocks, `woocommerce/product-search`, and 12 required core block
  dependencies for Product Collection and related blocks.

### Fixed

- Added shortcode pagination support by reading WooCommerce loop props and
  using WooCommerce's `product-page` query argument for paginated shortcode
  loops.

### Notes

- Native WooCommerce block styling is not included in v2.3.0. Client styling
  for native WooCommerce blocks belongs in the client repo.
- Tested with WordPress 6.8.

## v2.2.1 - 2026-05-18

### Added

- Added an inert `resources/css/client-tokens.css` starter template for client
  brand overrides.
- Added `docs/token-reference.md` with the overridable token contract and
  editor palette guidance for genuinely new client tokens.

### Fixed

- Limited WooCommerce product-card image hover effects to devices that support
  hover.
- Added site-title text fallbacks for missing header logo variants so empty
  logo URLs do not render broken images.

## v2.2.0 - 2026-05-18

### Changed

- Made `sobe/catalog-filters` own its mobile trigger and drawer shell so the
  drawer is portable outside the WooCommerce archive template. The supported
  production placement remains a single catalog-filters instance in the shop
  sidebar; standalone single-instance placements can open the block-owned drawer
  when a compatible product grid is present.
- Kept archive layout ownership to placement only: the shop archive now exposes
  an optional trigger slot above the product toolbar, while the block still owns
  the trigger element and drawer behavior.
- Mounted the block-owned filter drawer under `document.body` at runtime so the
  fixed drawer is not constrained by sidebar or archive stacking contexts.

### Fixed

- Removed the archive-owned mobile filter trigger and drawer markup.
- Fixed mobile shop layout so the relocated catalog filter widget shell does not
  leave an empty sidebar box after the trigger and drawer content move.
- Added an empty-content render guard so catalog filters emit no wrapper or
  inline script when no usable filter controls exist.
- Preserved drawer accessibility for the supported single-instance case:
  focus enters the drawer, remains trapped, returns to the opener, and Escape
  closes the drawer.

## v2.1.2 - 2026-05-18

### Fixed

- Added a prominent `resources/css/tokens.css` warning header directing client
  brand overrides to `resources/css/client-tokens.css`.
- Added a Jest checksum guard that fails when `resources/css/tokens.css`
  diverges from the committed platform baseline.

## v2.1.1 - 2026-05-17

### Added

- Added native client-namespace block manifest support so client blocks can declare explicit names such as `roxder/cta-banner` without patching platform block tests or pattern checks.
- Added optional client token override support for the editor `theme.json` build via `wpBoilerplate.themeJsonTokenOverrides` in `package.json`.

## v2.1.0 - 2026-05-17

### Fixed

- Fixed layout shell rendering so changing the client `prefix` no longer renames the default `sobe/site-header` and `sobe/site-footer` blocks.
- Added header navigation fallback output for fresh installs without an assigned primary menu.
- Fixed WooCommerce catalog column body classes so client `prefix` changes do not break the platform CSS selectors.
- Added default footer fallback links for fresh installs without footer widgets or a footer menu.
- Added a Blade fallback homepage for front pages without meaningful content.

## v2.0.2 - 2026-05-17

### Fixed

- Rewrote the client fork guide with a complete initial identity checklist, post-activation WordPress setup, client block workflow, and branch/PR upstream sync process.
- Clarified which `sobe` references client forks change and which remain upstream contracts.
- Aligned upstream sync and merge strategy docs with conflict-resolution guidance for client forks.

## v2.0.1

### Fixed

- Clarified repository hygiene after the v2 platform release, including ignored internal documentation and generated local files.

## v2.0.0

### Added

- Released the v2 platform contract for client forks, including the public block library, WooCommerce layer, search, side cart, dark mode, token system, hook contracts, and validation tooling.

# Sobe Theme

![Sobe Theme](screenshot.png)

> Source-available under the [PolyForm Noncommercial License 1.0.0](LICENSE.md) — free for non-commercial use with attribution; commercial use requires permission from Sobe Agency.

Sobe Agency's WordPress + WooCommerce theme platform — a Sage-based theme on Roots
Acorn 6. It's the shared upstream that Sobe's WordPress client builds fork from: a
client inherits the platform and customises through design tokens, hooks, and
namespaced blocks.

## What this is

`sobe-theme` is the internal platform, not a single-site theme. `main` stays
generic and production-hardened; brand, content, and proprietary client UI live in
separate private client repositories that track this upstream.

- **Platform (`main`, this repo):** design system, WooCommerce surfaces, universal
  `sobe/*` blocks, build pipeline, docs.
- **Client repo (private fork):** identity, tokens, navigation, content, and
  client-namespace blocks (e.g. `roxder/*`).

## Stack

- **Framework:** Roots Acorn 6 (Sage architecture, Blade templates)
- **Build:** Vite
- **Styles:** Tailwind CSS v4 + CSS custom-property design tokens
- **JS:** Alpine.js, GSAP + ScrollTrigger, Lenis, Swiper, noUiSlider
- **Navigation:** Swup full-page transitions
- **Commerce:** WooCommerce

## Features

- **Design tokens** — neutral defaults, dark-mode inversion, layout widths, spacing,
  type, shadows, transitions, z-index, WooCommerce aliases. Brand overrides via
  `resources/css/client-tokens.css` (never edit platform `tokens.css`).
- **App shell (Alpine)** — dark mode, mobile nav, search overlay, side-cart, toasts,
  smooth scroll, animation hooks.
- **Block library (`sobe/*`)** — hero, FAQ, product carousel, product feature,
  product-categories grid, brand carousel, our-brands, reviews slider, catalog
  filters. Manifest-driven registration.
- **WooCommerce layer** — catalog, PDP gallery/tabs, AJAX catalog filters (price
  slider, category/brand facets, result-scoped counts, clean URLs), side-cart and
  mini-cart fragments, wishlist surface, load-more pagination.
- **Layout shells** — `sobe/site-header` / `sobe/site-footer` via a pluggable layout
  router.
- **Search** — REST endpoint + modal UI.
- **SEO** — baseline metadata with plugin bypass.

## Requirements

- PHP **8.4+**
- Node **22.12+**
- WordPress **6.6+** (tested to **6.9.4**)
- WooCommerce (for commerce features)
- Composer

## Getting started

```bash
git clone git@github.com:antoniaksander/sobe-theme.git
cd sobe-theme

composer install   # PHP dependencies (Acorn, etc.)
npm install        # JS/CSS toolchain

npm run dev        # Vite dev server with HMR
npm run build      # production assets — run before committing JS/CSS changes
```

Activate the theme and set the header/footer layout in the Customizer.

## Starting a client build

Forked per client, not edited in place:

1. Fork from latest `main` into a private client repo.
2. Update identity: `style.css`, `config/theme.php` (`prefix`), `composer.json`,
   `package.json`, `vite.config.js`. Keep `textdomain` as `sobe`.
3. Override brand values in `resources/css/client-tokens.css`.
4. Keep universal blocks under `sobe/*`; add client blocks in a client namespace.
5. Extend behaviour through hooks before overriding Blade partials.
6. Pull platform updates from upstream; keeping shared files identical to upstream
   keeps merges clean.

Full walkthrough: [docs/client-fork-guide.md](docs/client-fork-guide.md).

## Deploying

Per-client, but two platform rules must travel with any deploy of the built theme:

- **Protect hashed build assets** from deletion (`rsync --filter='protect /public/build/***'`).
- **Clear Acorn's compiled cache before purging the page cache** (`wp acorn optimize:clear`).

## Validation & testing

```bash
npm test                  # Jest unit tests
npm run check:patterns    # block pattern checks
npm run check:upstream    # verify a client fork tracks the platform upstream
npm run build             # asset build validation
composer test:php         # Pest — PHP unit tests
composer analyse          # PHPStan static analysis
```

## Documentation

- [Contributing](CONTRIBUTING.md) — architecture rules and coding standards
- [Client Fork Guide](docs/client-fork-guide.md)
- [Block Authoring](docs/block-authoring.md) · [Hooks Reference](docs/hooks-reference.md) · [Token Reference](docs/token-reference.md)
- [Page Transitions](docs/page-transitions.md)
- [Client Boundary](docs/client-boundary.md) · [Merge Strategy](docs/merge-strategy.md) · [Upstream Sync Notes](docs/upstream-sync-notes.md)
- [Plugin Compatibility](docs/plugin-compatibility.md) · [Library Version Policy](docs/library-version-policy.md)

## License

Licensed under the [PolyForm Noncommercial License 1.0.0](LICENSE.md) — free to use,
modify, and share for **non-commercial** purposes with attribution; **commercial use
requires written permission** from Sobe Agency. Bundled open-source dependencies
retain their own licenses.

Copyright © Sobe Agency.

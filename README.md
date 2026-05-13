# WP Boilerplate

In-development WordPress theme boilerplate for agencies. Built on the **Native Hybrid Monolith** architecture: React in the editor, Blade on the frontend, and Tailwind CSS v4 everywhere.

## The Native Hybrid Monolith Philosophy

Most modern WordPress themes force you to choose between messy PHP spaghetti or a complex headless React setup. This bridges the gap with **The Five Laws**:

1. **Editor experience is React/JSX only.**
2. **Frontend rendering is Blade only.** (Block `save()` always returns `null`).
3. **WooCommerce integration is hooks only.** No overriding core WC templates.
4. **Styling is a strict CSS token cascade.**
5. **The DOM must be semantic.** No "div soup."

Before writing any code, read [CONTRIBUTING.md](CONTRIBUTING.md) to understand the architectural rules.
For maintenance and repo workflow, use the files in [docs/](docs/overview.md).

## Stack

- **PHP**: Sage / Roots Acorn (Laravel container for WordPress)
- **CSS**: Tailwind CSS v4, dynamic design tokens bridged automatically to `theme.json`
- **JS**: Alpine.js (interactivity), GSAP + Lenis (animations), React (block editor)
- **Blocks**: Native Gutenberg custom blocks

## Prerequisites

- **Node.js**: `>=22.12.0`
- **PHP**: `8.3` or higher
- **Composer**: v2.x

## Quick Start

```bash
# Install Node dependencies (Vite, Tailwind, WP Scripts)
npm install

# Install PHP dependencies (Roots Acorn, Sage, PHPStan)
composer install

# Initial production build (required before dev server)
npm run build

# Start Vite development server with HMR
npm run dev
```

> Note: Initial `npm install` may take a few minutes as it downloads `@wordpress/scripts` packages.

## Verify Your Installation

After installing, run the full quality check:

```bash
composer analyse        # PHP static analysis — must pass: [OK] No errors
npm test                # Block structural tests — must pass: 30/30
npm run check:patterns  # Pattern allowlist — must pass: all blocks in allowlist
npm run build           # Production build — must pass, bundles under budget
```

All four commands must pass before committing. CI enforces this on every pull request.

## Creating a New Block

We use a hybrid architecture: React for the editor UI, Blade for frontend rendering.

```bash
# Scaffold a new block
npm run make:block -- my-block-name

# With a specific category (default: sobe-general)
npm run make:block -- my-block-name --category=sobe-woocommerce
```

**Categories:**

- `sobe-general` — Default. Hero, callout, FAQ, content sections.
- `sobe-woocommerce` — Product carousels, feature grids, shop components.
- `sobe-content` — Text-heavy layouts, editorial patterns.

> Note: `sobe-layout` exists as an internal block category in `app/setup.php` and is not intended for scaffolded content blocks.

The scaffold creates:

- `resources/blocks/{slug}/` — Block registration, React edit UI, styles
- `resources/views/blocks/{slug}.blade.php` — Server-side render template

**After scaffolding:**

1. Define your `attributes` in `block.json`
2. Build the editor UI in `edit.jsx` (RichText, MediaUpload, InspectorControls, etc.)
3. Style the editor experience in `editor.scss`
4. Style the frontend in `style.scss`
5. Write the Blade template in `resources/views/blocks/{slug}.blade.php`

> **Important:** `save.jsx` returns `null` — this is intentional. WordPress never uses the client-side save output. The Blade template renders everything via `render_callback` in `app/setup.php`.

## Bundle Size Budget

CI enforces strict limits:

| Asset             | Limit  |
| ----------------- | ------ |
| JS (`app-*.js`)   | 250 KB |
| CSS (`app-*.css`) | 150 KB |

If `npm run build` exceeds these limits, CI fails. Optimize images, split chunks, or lazy-load components.

## WooCommerce

### Product gallery

The boilerplate ships with a **custom Swiper/PhotoSwipe product gallery** that replaces WooCommerce's default flexslider. This is a hard dependency: `app/woocommerce.php` dequeues `flexslider`, `photoswipe`, `photoswipe-ui-default`, and `wc-zoom` on single product pages.

**If a client project does not need the custom gallery**, remove the four `wp_dequeue_script()` calls in `app/woocommerce.php` (look for the comment "Dequeue WC scripts this theme replaces").

### Configuring the gallery aspect ratio

The single-product gallery reserves its height before JavaScript initializes to prevent Cumulative Layout Shift (CLS). The reserved aspect ratio must match your `woocommerce_single` image crop setting. Change it in `config/theme.php`:

```php
'wc_gallery_aspect_ratio' => '1 / 1', // default: square
// Other common values: '4 / 5', '3 / 4'
```

Verify the correct ratio: go to WooCommerce → Settings → Products → Product images and note the **Single product image** dimensions. If width and height differ, use `width / height` as the ratio.

### WooCommerce scripts strategy

`app/woocommerce.php` loads WooCommerce scripts conditionally by page context to minimize Total Blocking Time (TBT):

| Context | Strategy |
| --- | --- |
| Single product page | Full `WC_Frontend_Scripts::load_scripts()` (required for variation params), then dequeues gallery scripts |
| Cart / Checkout / Account | Full `WC_Frontend_Scripts::load_scripts()` |
| All other pages | Only `wc-cart-fragments` for the side-cart fragment refresh |

If your Alpine side cart manages state entirely via the Store API (check `window.themeCartParams.storeApiAddUrl`), you can remove the `wp_enqueue_script('wc-cart-fragments')` line to eliminate jQuery from non-product pages entirely.

## AI-Ready Development

This repository has `CLAUDE.md` — an orientation document for AI coding assistants. It covers the Five Laws, critical file map, the hybrid hook model, and common anti-patterns. If you use Claude Code, Cursor, or GitHub Copilot, reference this file at the start of each session for grounded context. Send me an email sander@sobe.agency to get the latest version.

## How to Contribute

Contributions are welcome. Please read the architectural laws first.

1. Fork the repo
2. Clone your fork: `git clone https://github.com/antoniaksander/WP-boilerplate.git`
3. Create a feature branch: `git checkout -b feat/your-new-feature`
4. Develop: Follow the Five Laws and the Anti-Pattern Registry in CONTRIBUTING.md
5. Push and open a PR against `main`

> PRs that violate architecture (e.g., PHP hooks in Blade templates, hardcoded hex colors outside `tokens.css`) will be rejected.

## Troubleshooting

### `phpstan: command not found`

Run `composer install` (not `composer update`). The lock file pins exact versions including PHPStan.

### `View [sections.footer-layout-1] not found`

The footer uses a simple fallback until the header/footer architecture is finalized. Customize `resources/views/sections/footer.blade.php` per project.

### `--dev` flag deprecated

Composer 3 removed `--dev`. Use `composer install` (dev packages install by default).

## License

Copyright (c) 2026 Sander Antoniak and Roots Software LLC.

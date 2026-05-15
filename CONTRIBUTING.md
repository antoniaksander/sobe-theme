# Contributing

`main` is the Sobe platform contract. Keep it generic, production-ready, and slow to break. Client presentation belongs in client repositories.

## Rules

- Universal blocks stay in the `sobe/*` namespace.
- Client-specific blocks live in a client namespace such as `roxder/*`.
- Extend with hooks first; override Blade files only when a hook cannot express the structural change.
- Use `sobe` for every translation textdomain.
- Keep brand colors, logos, navigation decisions, campaign content, and proprietary UI out of public `main`.

## Block System

Blocks are dynamic Gutenberg blocks:

- Editor UI: `resources/blocks/{slug}/edit.jsx`
- Metadata: `resources/blocks/{slug}/block.json`
- Frontend output: `resources/views/blocks/{slug}.blade.php`
- Runtime script when needed: `resources/blocks/{slug}/view.js`
- `save.jsx` returns `null`
- Registration is driven by `resources/blocks/blocks-manifest.json`

Use production blocks as references:

- `hero` for media, copy, CTA, layout controls, and render hooks
- `faq` for repeatable attributes and frontend behavior
- `product-carousel` for WooCommerce queries and Swiper behavior
- `catalog-filters` for AJAX-backed WooCommerce block behavior
- `site-header` and `site-footer` for non-inserter layout examples

Scaffold generic blocks with:

```bash
npm run make:block -- your-block-name
```

## WooCommerce

The platform owns the shared WooCommerce integration: catalog, PDP shell, gallery, tabs, related products, catalog filters, side-cart, mini-cart count, wishlist surface, and base styling.

Client repos customize by using hooks documented in [docs/hooks-reference.md](docs/hooks-reference.md). Only override Blade templates when the change is structural and hook-based customization would be impractical.

## Forking For A Client

Do not modify upstream `sobe/*` blocks in place. Copy a platform example into the client namespace:

1. Copy `resources/blocks/site-header` to `resources/blocks/roxder-site-header` or another client-owned folder.
2. Set `name: roxder/site-header` in `block.json`.
3. Add the new slug to `resources/blocks/blocks-manifest.json`.
4. Customize the copy in the client repo.

This keeps `git merge upstream/main` focused on platform changes instead of client markup conflicts.

## Textdomain

Use `sobe` in PHP, Blade, JSX, JS i18n calls, and `block.json` metadata. `sage` may appear only as a framework/dependency name, never as a theme textdomain.

## Validation

Before committing:

```bash
npm test
npm run check:patterns
npm run build
composer analyse
```

For browser-facing changes, verify the Local site and check the console. For WooCommerce changes, test shop archive, product detail, add-to-cart, side-cart, catalog filters, and checkout entry.

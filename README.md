# WP-boilerplate

Production-ready WordPress theme platform for Sobe agency client builds.

This repo is shared infrastructure, not a client theme. Client repositories fork from `main`, inherit the platform, and customize through tokens, Customizer settings, hooks, and client-namespace blocks.

## Included

- Design token system with neutral defaults, dark mode inversion, layout widths, spacing, type, shadows, transitions, z-index, and WooCommerce aliases
- Alpine app shell for dark mode, mobile navigation, search overlay, side-cart, toasts, smooth scroll, and animation hooks
- Public block library: hero, FAQ, product-carousel, product-feature, product-categories-grid, brand-carousel, our-brands, reviews-slider, catalog-filters
- Layout examples: `sobe/site-header` and `sobe/site-footer` rendered through hidden layout patterns
- WooCommerce platform layer: catalog, PDP gallery/tabs, side-cart, catalog filters, mini-cart fragments, wishlist surface, load-more pagination
- Search endpoint and modal UI
- Baseline SEO metadata with plugin bypass
- Manifest-driven block registration
- Vite + Tailwind CSS asset pipeline
- Jest, pattern checks, PHPStan, and build validation

## Client Forks

1. Fork from latest `main`.
2. Update client identity in `style.css`, `config/theme.php`, `composer.json`, `package.json`, `README.md`, and `vite.config.js`.
3. Change `prefix` in `config/theme.php` for client-owned settings and CSS classes, but keep `textdomain` as `sobe`.
4. Override brand values in `resources/css/tokens.css`.
5. Keep universal blocks under `sobe/*`; create client-specific blocks in a client namespace such as `roxder/*`.
6. Configure the active WordPress site: navigation, header layout, logo, footer widgets, homepage, and dark mode.
7. Extend WooCommerce, search, wishlist, hero, and block behavior through hooks before overriding Blade partials.
8. Add client logos, fonts, navigation, content, and private blocks in the client repo.

Do not rename universal `sobe/*` blocks in place. The default header and footer shell blocks stay `sobe/site-header` and `sobe/site-footer` even after changing the client prefix. If a client deliberately needs a custom shell, register a replacement block and point the layout router at it with the `sobe/layout/block_name` filter.

## Documentation

- [Contributing](CONTRIBUTING.md)
- [Client Fork Guide](docs/client-fork-guide.md)
- [Hooks Reference](docs/hooks-reference.md)
- [Client Boundary](docs/client-boundary.md)
- [Merge Strategy](docs/merge-strategy.md)
- [Library Version Policy](docs/library-version-policy.md)

## Validation

```bash
npm test
npm run check:patterns
npm run build
composer analyse
```

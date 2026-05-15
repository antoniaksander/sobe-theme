# Library Version Policy

Public dependencies are the union of libraries used by public platform features and blocks. Private client blocks may add their own dependencies in the client repo. When a private block is promoted to public, its runtime dependencies are promoted with it.

## Runtime Libraries

| Library | Version Policy | Load Bearing | Used By |
|---------|----------------|--------------|---------|
| `alpinejs` | Caret pinned within v3, currently `^3.15.11` | High | App shell, dark mode, nav, search, side-cart, toasts |
| `@alpinejs/focus` | Match Alpine minor, currently `^3.15.11` | High | Focus traps for search, nav, side-cart |
| `swiper` | Caret pinned within v12, currently `^12.1.3` | High | Product carousel, PDP gallery, product categories grid |
| `gsap` | Caret pinned within v3, currently `^3.15.0` | Medium-high | Animation bus, sticky header, AJAX refresh hooks |
| `lenis` | Caret pinned within v1, currently `^1.3.23` | Medium | Smooth scroll, scroll locking coordination |
| `nouislider` | Caret pinned within v15, currently `^15.8.1` | Medium | Catalog filter price range |

Load-bearing means replacing the library would affect shared markup, events, behavior, or multiple client repos. Swappable means the replacement can be contained inside one block or feature.

## Upgrade Cadence

- Review public runtime libraries quarterly.
- Upgrade immediately for security fixes.
- Upgrade on demand when a platform feature needs a bug fix.
- Do not auto-merge dependency majors into client repos.

## Breaking Major Protocol

1. Open a platform upgrade branch.
2. Read the upstream migration guide.
3. Run `npm test`, `npm run check:patterns`, `npm run build`, and `composer analyse`.
4. Browser-check every public feature that imports the library.
5. Add client migration notes before merging.
6. Merge to `main` only after Local browser verification.

## Feature Checks By Library

| Library | Required Checks |
|---------|-----------------|
| `alpinejs` | Dark mode persistence, mobile nav, search overlay, side-cart, toasts |
| `@alpinejs/focus` | Escape key, focus trap, focus return, inert background |
| `swiper` | Product carousel, PDP gallery, product category grid mobile behavior |
| `gsap` | Animation bus, sticky header, AJAX refresh animation reset, reduced motion |
| `lenis` | Smooth scroll, drawer lock, reduced motion, mobile fallback |
| `nouislider` | Catalog filter price selection, URL state, AJAX refresh |

# WP Boilerplate

Sobe-branded thin infrastructure for WordPress agency themes. This repository is the upstream engine: block registration, Vite assets, minimal Blade shell, generic WordPress setup, WooCommerce support, and hardening.

The full working Sobe theme lives in `WP-boilerplate-demo`. Client projects can fork the demo once, then track this repository as `upstream` for infrastructure updates only.

## Repository Roles

- `WP-boilerplate`: thin Sobe infrastructure. Use this as `upstream`.
- `WP-boilerplate-demo`: rich Sobe starter with blocks, patterns, sidecart, dark mode, wishlist, headers, footers, and demo presentation. Fork once only.
- Client theme: owns branding, templates, blocks, customizer, WooCommerce presentation, and business logic.

## New Client Workflow

```bash
git clone <client-repo>
cd <client-repo>
git remote add upstream https://github.com/antoniaksander/WP-boilerplate.git
npm run check:upstream
```

Do not set `upstream` to `WP-boilerplate-demo`.

## Stack

- PHP: Roots Acorn / Sage-style Blade theme structure
- CSS/JS: Vite and Tailwind CSS v4
- Blocks: dynamic Gutenberg blocks rendered with Blade
- WooCommerce: generic support and wrapper hooks only

## Install

```bash
npm install
composer install
npm run build
```

## Validate

```bash
npm test
npm run check:patterns
npm run build
composer analyse
```

## What Belongs Here

- Build and asset pipeline
- Manifest-based dynamic block registration
- Generic WordPress theme setup
- Generic WooCommerce support
- Security hardening
- Minimal Blade shell
- One example block proving the mechanism

## What Does Not Belong Here

- Client branding, fonts, tokens, headers, footers, and templates
- Sidecart, wishlist, dark mode, search overlays, and customizer policy
- Product brand taxonomies, catalog filters, load-more UX, and PDP presentation
- WooCommerce template overrides
- Client-specific blocks and patterns

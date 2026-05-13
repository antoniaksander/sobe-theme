# WP Boilerplate

Sobe-branded thin infrastructure for WordPress agency themes. This repository is the upstream engine: block registration, Vite assets, minimal Blade shell, generic WordPress setup, WooCommerce support, and hardening.

The full working Sobe theme lives in `WP-boilerplate-demo`. Client projects can fork the demo once, then track this repository as `upstream` for infrastructure updates only.

## Repository Roles

- `WP-boilerplate`: thin Sobe infrastructure. Use this as `upstream`.
- `WP-boilerplate-demo`: rich Sobe starter with blocks, patterns, sidecart, dark mode, wishlist, headers, footers, and demo presentation. Fork once only.
- Client theme: owns branding, templates, blocks, customizer, WooCommerce presentation, and business logic.

## Version Policy

- `v1.x.x` (`v1.0.0-rich-sobe-starter`): full theme with blocks, patterns, customizer, sidecart, and presentation features. Existing clients pin to this line. New clients should not use it.
- `v2.x.x` (`v2.0.0-thin-infra`): infrastructure only, including the block system, asset pipeline, WooCommerce wrappers, setup, and hardening. New clients track this as `upstream`.

Existing rich clients should not blindly merge `upstream/main`. Cherry-pick specific security or infrastructure fixes from v2, or rebase intentionally during a maintenance window. See [Merge Strategy](docs/merge-strategy.md) for the detailed policy.

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

# Upstream Sync Notes

## Warnings

- `demo/sobe` is a sandbox and reference branch. Client repos fork `main`, never `demo/sobe`.
- `v1.0.0-rich-sobe-starter` archives the old full-theme state.
- `enrichment-attempt-1` records the narrow pre-v2 enrichment attempt.
- v2 makes WooCommerce, search, side-cart, wishlist UI shell, tokens, JS app shell, and the public block library platform-owned.

## Current State

- `main` is the platform contract for client repos.
- Universal blocks keep the `sobe/*` namespace.
- Client-specific blocks use a client namespace such as `roxder/*`.
- Block registration is manifest-driven through `resources/blocks/blocks-manifest.json`.
- Textdomain is `sobe`.
- `product_brand` is platform-owned and can be disabled with `sobe/product_brand/register`.
- Runtime library policy is documented in [library-version-policy.md](library-version-policy.md).

## Sync Notes For Clients

- Pull platform changes manually into a feature branch.
- Prefer hooks over file overrides.
- Re-test shop archive, PDP, side-cart, search modal, wishlist surfaces, and all custom client blocks after each upstream merge.

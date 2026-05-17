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

- Pull platform changes manually into a sync branch created from client `main`.
- Merge `upstream/main` into that sync branch, resolve conflicts, run validation, and open a PR back to client `main`.
- Treat merge conflicts as expected sync work, not as an error. Both platform and client repos commonly touch `.gitignore`, `resources/css/client-tokens.css`, `resources/blocks/blocks-manifest.json`, `app/blocks.php`, and client-adjusted tests. `resources/css/tokens.css` should only conflict when the platform intentionally changes the token contract.
- Prefer hooks over file overrides. When a conflict involves platform hooks, keep the `sobe/*` hook names intact and re-apply client behavior around them.
- For `.gitignore` conflicts, keep the union of platform and client rules unless a rule is clearly obsolete.
- Re-test shop archive, PDP, side-cart, search modal, wishlist surfaces, header/footer layout shell blocks, dark mode, and all custom client blocks after each upstream merge.

See [client-fork-guide.md](client-fork-guide.md#upstream-sync) for the full branch and PR workflow.

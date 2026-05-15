# Merge Strategy

## New Clients

1. Fork from latest `main`.
2. Change `prefix` in `config/theme.php`.
3. Keep `textdomain` as `sobe`.
4. Override brand tokens in `resources/css/tokens.css`.
5. Add logos, navigation, content, and client-specific blocks in a client namespace.
6. Extend platform WooCommerce/search/block behavior through documented hooks.

## Existing Clients

Existing client repos should pull platform updates deliberately. Upstream pulls can include:

- Asset pipeline improvements
- Token and dark-mode platform changes
- Public block improvements
- WooCommerce hook contract improvements
- Security patches
- Testing/linting upgrades

## Danger Zone

These changes can break client forks and need migration notes:

- Removing or renaming a `sobe/*` block
- Removing a documented `sobe/<feature>/<action>` hook
- Changing hook parameters or return types
- Changing `sobe_render_layout_pattern()` signature
- Replacing app-shell event names or `localStorage.theme`
- Changing WooCommerce fragment selectors such as `div.sobe-side-cart-content` or `span.sobe-cart-count`
- Renaming `product_brand` without a compatibility path

## Sync Cadence

- Review upstream quarterly at minimum.
- Never auto-merge platform updates into client `main`.
- Merge upstream into a client feature branch.
- Run automated validation.
- Browser-check Local before merging to client `main`.

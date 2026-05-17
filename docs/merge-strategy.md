# Merge Strategy

## New Clients

1. Fork from latest `main`.
2. Update client identity in `style.css`, `config/theme.php`, `composer.json`, `package.json`, `README.md`, and `vite.config.js`.
3. Change `prefix` in `config/theme.php`, but keep `textdomain` as `sobe`.
4. Confirm the layout shell blocks render after the prefix change. The default shell remains `sobe/site-header` and `sobe/site-footer`; client-namespaced shell blocks are only needed for deliberate replacement.
5. Override brand tokens in `resources/css/tokens.css`.
6. Add logos, navigation, homepage content, footer widgets, and client-specific blocks in a client namespace.
7. Extend platform WooCommerce/search/block behavior through documented hooks.

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
- Create a sync branch from client `main`, merge `upstream/main` into that branch, and open a PR back to client `main`.
- Expect merge conflicts. Resolve them by preserving upstream contracts and re-applying client-specific changes narrowly.
- For `.gitignore`, keep the union of platform and client rules unless a rule is clearly obsolete.
- Run automated validation.
- Browser-check Local before merging the sync PR to client `main`.

See [client-fork-guide.md](client-fork-guide.md#upstream-sync) for the detailed workflow and conflict-resolution notes.

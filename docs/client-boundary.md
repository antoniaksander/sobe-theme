# Client vs Platform Boundary

| File/Area | Platform Owns | Client Owns |
|-----------|----------------|--------------|
| `config/theme.php` | Default `sobe` prefix, `sobe` textdomain, neutral config defaults | Client prefix and client-specific config values |
| `resources/css/tokens.css` | Token names, scales, neutral defaults, dark-mode contract | No client edits. Brand colors and optional brand font token overrides belong in `resources/css/client-tokens.css`. |
| `resources/js/app.js` | Alpine app shell, event names, dark mode, nav, search, side-cart, toasts | Client scripts that consume documented events |
| `resources/views/layouts/app.blade.php` | Platform layout shell, app wrapper, SEO baseline, overlays | Rare structural overrides in client repo |
| `resources/blocks/sobe/*` | Universal production blocks and examples | Client copies under client namespace |
| `app/blocks.php` | Manifest registration, categories, allowlist hook | Client manifest entries for client blocks |
| `app/setup-customizer.php` | Generic platform settings | Client-specific settings in client repo |
| `app/setup-patterns.php` | Hidden layout patterns, `product_brand` registration contract | Client pattern additions |
| `app/setup-search.php` | Search endpoint, params, result hooks | Search result tuning via hooks |
| `app/woocommerce*.php` | Shared WC catalog, PDP, filters, side-cart, hook contracts | Hook callbacks and rare template overrides |
| `resources/views/woocommerce/` | Platform WC templates | Client overrides only for structural changes |
| `resources/views/components/` | Generic components and WC/search shells | Client-only components |
| `resources/patterns/` | Hidden platform layout patterns | Client-owned patterns in client repo |
| `resources/config/core-allowed-blocks.json` | Core/WC allowlist baseline | Client extensions through hook/config |

## Rule

If a platform file needs client-specific markup, copy the relevant block or view into the client repo and wire it through hooks or client namespace registration. Do not edit upstream `sobe/*` files in place for a client.

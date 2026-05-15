# Plugin Compatibility

Track only generic, reusable platform plugin compatibility in this file.

Client-specific plugin notes belong in each private client repo.

## Status Meanings

- `supported`: intentionally supported in the platform
- `partial`: works with caveats or limited integration
- `client-only`: used in a client repo, not supported by the starter itself
- `unsupported`: known to conflict or not integrated

## Compatibility Matrix

| Plugin                             | Version Tested | Last Verified | WP / Woo Tested | Status    | Affected Areas                                              | Notes                                    | Link                                                                   |
| ---------------------------------- | -------------- | ------------- | --------------- | --------- | ----------------------------------------------------------- | ---------------------------------------- | ---------------------------------------------------------------------- |
| WooCommerce                        | 10.7.0         | 13-05-2026    | Woo             | supported | shop, cart, checkout, account, Sobe WooCommerce blocks      | Core commerce dependency of this platform | [link](https://en-gb.wordpress.org/plugins/woocommerce/)               |
| Variation Swatches for WooCommerce | 2.2.3          | 13-05-2026    | Woo             | supported | shop, Woo catalog, product view, side-cart widget attributes | Optional platform compatibility layer    | [link](https://en-gb.wordpress.org/plugins/woo-variation-swatches/)    |
| Rank Math SEO                      | 1.0.269        | 13-05-2026    | WP/Woo          | partial   | SEO attributes                                              | Platform baseline SEO bypasses when active | [link](https://en-gb.wordpress.org/plugins/seo-by-rank-math/)        |
| CookieYes GDPR Cookie Consent      | 3.4.2          | 13-05-2026    | WP/Woo          | supported | Europe GDPR needs                                           | Optional platform compatibility layer    | [link](https://en-gb.wordpress.org/plugins/cookie-law-info/)           |
| YITH WooCommerce Wishlist          | 4.14.0         | 13-05-2026    | Woo             | partial   | Shop, product                                               | Optional wishlist provider adapter       | [link](https://en-gb.wordpress.org/plugins/yith-woocommerce-wishlist/) |

Add rows as integrations are actually tested.

## Per-Plugin Notes Template

When adding support, capture:

- plugin name
- plugin version tested
- last verified date
- WordPress version tested
- WooCommerce version tested, if relevant
- support status
- affected theme areas
- files commonly touched
- known conflicts
- whether support belongs in the platform or only in a client repo

## Rules

- Do not list a plugin as `supported` unless it has been tested intentionally.
- Always update `Last Verified` when re-checking an integration.
- If the plugin changes WooCommerce templates, hooks, or frontend JS assumptions, document that explicitly.
- If a plugin is only used by one client, keep detailed notes in that client repo and only add a short generic note here if it affects starter architecture.

# Client Fork Guide

Client repos fork `main`. They do not fork `demo/sobe`.

## Initial Setup

1. Fork or clone this repo into the client project.
2. Add the public boilerplate remote as `upstream`.
3. Change `prefix` in `config/theme.php` to the client prefix, for example `roxder`.
4. Keep `textdomain` as `sobe`.
5. Run validation:

```bash
npm test
npm run check:patterns
npm run build
composer analyse
```

## Brand Tokens

Override brand values in `resources/css/tokens.css`.

Keep token names stable. Client changes should set values, not rename the contract. Typical client-owned tokens include:

- `--c-primary`
- `--c-accent`
- `--font-sans`
- `--font-heading`
- button color aliases
- logo-dependent spacing or header sizing

The bundled Satoshi and CabinetGrotesk fonts are platform defaults. A client may keep them or replace the font token values and add its own font files.

## Blocks

Universal blocks stay in `sobe/*`. Client-specific blocks use a client namespace.

To customize a platform example:

1. Copy the block folder.
2. Rename the folder to a client-owned slug.
3. Change `block.json` `name` to the client namespace.
4. Add the new slug to `resources/blocks/blocks-manifest.json`.
5. Customize the copy.

Do not rename upstream `sobe/*` blocks in place.

## WooCommerce Customization

Use hooks before overriding templates. Common extension points are documented in [hooks-reference.md](hooks-reference.md).

Use hooks for:

- catalog query changes
- columns and per-page values
- product card data or view swap
- PDP gallery image list or settings
- PDP tabs and tab content
- related products
- side-cart content and fragments
- mini-cart count
- wishlist provider integration

Only override Blade templates when the required change is structural.

## Search

The platform provides `/wp-json/sobe/v1/search` and a search overlay. Clients can change post types, query args, result shape, and overlay view through `sobe/search/*` hooks.

## Product Brands

The platform registers `product_brand` for WooCommerce products. Clients that do not use brands can ignore it. To opt out:

```php
add_filter('sobe/product_brand/register', '__return_false');
```

Blocks and filters that use brands read the taxonomy through `sobe/catalog_filters/brand_taxonomy`.

## Upstream Sync

1. `git fetch upstream`
2. Create a sync branch in the client repo.
3. Review upstream commits and breaking notes.
4. Merge `upstream/main`.
5. Resolve conflicts without modifying upstream `sobe/*` files for client presentation.
6. Run automated validation.
7. Browser-check Local.
8. Merge to client `main`.

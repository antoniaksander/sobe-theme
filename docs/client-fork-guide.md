# Client Fork Guide

Client repos fork `main`. They do not fork `demo/sobe`.

This guide describes the v2.1.x process for turning the platform into a client
theme. It is intentionally conservative: keep the upstream contract intact,
move client identity into client-owned files, and prefer hooks or copied blocks
over editing platform-owned behavior in place.

## Initial Setup

1. Fork or clone this repo into the client project.
2. Add the public boilerplate remote as `upstream`.
3. Create the client theme folder name. The folder name matters because built
   asset URLs include it.
4. Update the theme identity checklist below before configuring the site in the
   WordPress admin.
5. Install dependencies and run validation.

```bash
npm install
composer install
npm test
npm run check:patterns
npm run build
composer analyse
```

### Identity Checklist

Update these files before the first client build:

| File | Change | Notes |
| --- | --- | --- |
| `style.css` | Change the theme header block: `Theme Name`, `Theme URI`, `Description`, `Author`, and `Author URI`. | Use client-owned public URLs when they exist. If the final URLs are not ready, use obvious placeholders that the client can replace before launch. Keep `Text Domain: sobe` unless a later translation migration explicitly changes it. |
| `config/theme.php` | Change `prefix` from `sobe` to the client prefix, for example `roxder`. | This controls client-owned Customizer setting keys, image size names, and generated handles. It does not rename the platform layout shell blocks. Keep `textdomain` as `sobe`. |
| `composer.json` | Change `name` and `description`. | Use a package name that belongs to the client or project, for example `roxder/wp-theme`. |
| `package.json` | Change `name`. | `package-lock.json` will update its `name` fields the next time `npm install` writes the lockfile. |
| `README.md` | Replace the platform README with a client-specific README. | Keep a short note that the client repo tracks the Sobe platform upstream if that helps future maintainers. |
| `vite.config.js` | Change `base` to the real theme folder path, for example `/wp-content/themes/roxder/public/build/`. | If this path does not match the actual theme folder, built CSS, JS, and block asset URLs will break. |

Change `config/theme.php` `prefix` before using the Customizer. Customizer
settings are stored in the database under keys that include the prefix, so
changing the prefix after configuring logos, header options, footer options, or
WooCommerce display options leaves those settings orphaned under the old keys.

## What Changes And What Stays

Do not rename every `sobe` string. Some references are client identity; others
are upstream contracts.

| Kind | Client changes? | Why |
| --- | --- | --- |
| Theme `prefix` in `config/theme.php` | Yes. Use the client prefix, for example `roxder`. | Client forks need isolated Customizer keys, handles, and generated names. |
| Theme identity in `style.css`, `composer.json`, `package.json`, and `README.md` | Yes. | These describe the client project, not the platform. |
| Client-specific block namespace | Yes. Use names such as `roxder/promo-grid`. | Private client blocks should not appear as upstream universal blocks. |
| `sobe` i18n textdomain in `__()`, `_e()`, `block.json` `textdomain`, and related translation calls | No. Keep `sobe`. | The textdomain is the upstream translation contract in v2.0.x. Renaming it fragments translation files and breaks upstream compatibility. |
| `sobe/*` hook namespace in `apply_filters()` and `do_action()` calls | No. Keep `sobe/*`. | Hooks are the integration contract. Renaming them breaks existing extensions and upstream merge compatibility. |
| Universal platform block names such as `sobe/hero`, `sobe/product-carousel`, `sobe/site-header`, and `sobe/site-footer` | No. Keep `sobe/*`. | These are shared platform blocks. Customize them through attributes, styles, hooks, or copied client blocks. |

## Setting Up Your Site After Activation

After activating the theme, configure the WordPress site before judging the
frontend. A fresh client fork can look broken if the navigation, homepage, logo,
footer widgets, or layout shell blocks have not been set up yet.

### 1. Confirm The Layout Shell Blocks Render

The current theme is not a block theme and does not use Site Editor template
parts. Header and footer output comes from the Sage layout:

- `resources/views/layouts/app.blade.php` calls `sobe_render_layout_pattern()`
  for the header.
- `resources/views/sections/footer.blade.php` calls the same helper for the
  footer.
- `app/setup-demo-layout.php` renders the stable platform shell blocks
  `sobe/site-header` and `sobe/site-footer`.
- `app/setup-patterns.php` registers hidden header/footer layout patterns from
  `resources/patterns/`, but those patterns are examples and are not inserted
  through the Site Editor.
- The platform ships hidden layout block examples in
  `resources/blocks/site-header` and `resources/blocks/site-footer`.

Changing `config/theme.php` `prefix` must not rename the layout shell. The shell
block namespace is platform infrastructure, like the `sobe/*` hook namespace and
the `sobe` textdomain. A client only needs custom shell blocks if it deliberately
wants to replace the platform shell. In that case, use the
`sobe/layout/block_name` filter to return a different block name for `header` or
`footer`.

### 2. Create And Assign Navigation

1. In WordPress admin, go to `Appearance -> Menus`.
2. Create the primary navigation menu.
3. Add the pages, product categories, shop links, or custom links needed for the
   client.
4. Assign it to `Primary Navigation`.
5. Save the menu.

If no menu is assigned to that location yet, the header falls back to a page
list, or a Home link when the site has no pages. Assigning a real menu replaces
that fallback.

### 3. Configure Header, Logo, And Footer

Go to `Appearance -> Customize`.

In `Header Options`:

- Choose `Header: Layout`.
- Upload `Logo: Light`.
- Upload `Logo: Dark` if dark mode will be used.
- Enable or disable `Header: Dark Mode Toggle`.
- Enable or disable `Header: Side Cart`.
- Enable `Header: Wishlist Icon` only when a wishlist provider is available.

Header variants:

| Variant | Use when |
| --- | --- |
| `header-1` | Default layout: logo left, menu centered, actions right. Good for most ecommerce sites with a balanced main nav. |
| `header-2` | Menu left, logo centered, actions right. Good when brand presence should stay visually centered. |
| `header-3` | Hamburger on all viewports, logo centered, actions right. Good for sparse, editorial, or mobile-first navigation. |

In `Footer Options`:

- Choose `Footer: Layout`.
- `layout-2` renders the minimal brand and widget footer.
- `none` hides the footer.

Footer content can come from the `Footer Navigation` menu location or the
`Footer` widget area. With neither configured, the theme renders a small fallback
link group so a fresh install still has a useful footer. Go to `Appearance ->
Menus` to assign footer navigation, or `Appearance -> Widgets` to add blocks or
widgets to `Footer`. The default footer also uses the site name, tagline, and
current year.

### 4. Set A Homepage

1. Create a page for the homepage.
2. Add client content blocks. A typical first pass uses `sobe/hero`, product or
   category blocks, brand blocks, FAQ, reviews, and any client-specific blocks.
3. Go to `Settings -> Reading`.
4. Set `Your homepage displays` to `A static page`.
5. Choose the homepage page.
6. Choose a posts page only if the client site needs a blog index.

The platform includes `resources/views/front-page.blade.php` for the front page
route. If the selected front page has no meaningful content yet, the theme
renders a Blade fallback homepage so a fresh install is not blank. Adding real
page content replaces that fallback.

### 5. Enable And Preview Dark Mode

Dark mode support is built into the app shell, but the header toggle is off by
default.

1. Go to `Appearance -> Customize -> Header Options`.
2. Enable `Header: Dark Mode Toggle`.
3. Save and publish.
4. Open the frontend and click the sun/moon button in the header action area.

The toggle stores the visitor preference in `localStorage.theme`. Preview both
states on real frontend pages, especially pages with logos, product cards,
forms, and custom client blocks. Use `Logo: Dark` when the light logo does not
work on dark backgrounds.

## Brand Tokens

Override brand values in `resources/css/tokens.css`.

Keep token names stable. Client changes should set values, not rename the
contract. Typical client-owned tokens include:

- `--c-primary`
- `--c-accent`
- `--font-sans`
- `--font-heading`
- button color aliases
- logo-dependent spacing or header sizing

The bundled Satoshi and CabinetGrotesk fonts are platform defaults. A client
may keep them or replace the font token values and add its own font files.

## Blocks

Universal blocks stay in `sobe/*`. Client-specific blocks use a client
namespace.

### Copy A Platform Block

Copying a platform block is the safest way to create client-specific behavior
without mutating upstream blocks in place.

1. Copy the block folder, for example `resources/blocks/hero` to
   `resources/blocks/roxder-hero`.
2. Copy the matching Blade view, for example `resources/views/blocks/hero.blade.php`
   to `resources/views/blocks/roxder-hero.blade.php`.
3. In the copied `block.json`, update every identity field:
   - `name`, for example `roxder/hero`
   - `title`
   - `description`
   - `category`, if the block should appear in a client category
4. Keep `textdomain` as `sobe` in v2.0.x.
5. Update imports, labels, CSS class names, and editor preview text only where
   they are actually client-owned.
6. Add the copied folder slug to `resources/blocks/blocks-manifest.json`.
7. Run `npm test`, `npm run check:patterns`, and `npm run build`.

Do not leave copied metadata as `sobe/example`, `sobe/hero`, or another platform
block name. That mistake is easy to miss because the block may still build, but
WordPress will register the wrong block identity.

### Register A Client Block Category

The platform registers its categories in `app/blocks.php` with the
`block_categories_all` filter:

- `sobe-general`
- `sobe-woocommerce`
- `sobe-content`
- `sobe-layout`

Client forks can register a separate category, for example `roxder`, so private
blocks group separately from `sobe/*` blocks in the inserter. Add the category
in the client fork using the same filter pattern and leave the platform
categories intact.

Example category shape:

```php
[
    'slug' => 'roxder',
    'title' => __('Roxder', 'sobe'),
    'icon' => 'layout',
]
```

### Blocks Manifest

`resources/blocks/blocks-manifest.json` is the source list for block folders.
The runtime and Vite entries iterate over the manifest keys, so the key must
match the folder slug under `resources/blocks`.

Entry fields:

| Field | Required | Notes |
| --- | --- | --- |
| `category` | Yes | Must match `block.json` `category`. The platform tests assert this. |
| `name` | No | Full block name for tooling and tests. Defaults to `sobe/<slug>` when omitted. Add it for client-namespace blocks, for example `roxder/hero`. The runtime still reads the registered block name from `block.json`, so keep both values in sync when `name` is present. |

Example client block entry:

```json
{
  "roxder-hero": {
    "name": "roxder/hero",
    "category": "roxder"
  }
}
```

### Block Tests

The platform block metadata test reads `manifest[slug].name` when present and
falls back to `sobe/<slug>` for platform blocks. Client forks should not need to
patch the platform test when adding a client-namespace block.

## WooCommerce Customization

Use hooks before overriding templates. Common extension points are documented in
[hooks-reference.md](hooks-reference.md).

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

The platform provides `/wp-json/sobe/v1/search` and a search overlay. Clients
can change post types, query args, result shape, and overlay view through
`sobe/search/*` hooks.

## Product Brands

The platform registers `product_brand` for WooCommerce products. Clients that
do not use brands can ignore it. To opt out:

```php
add_filter('sobe/product_brand/register', '__return_false');
```

Blocks and filters that use brands read the taxonomy through
`sobe/catalog_filters/brand_taxonomy`.

## Upstream Sync

Sync platform updates through the same branch and PR process used for feature
work. Do not merge upstream directly into client `main`.

1. Make sure client `main` is clean and current.
2. Fetch upstream.
3. Create a sync branch from client `main`.
4. Merge `upstream/main` into that sync branch.
5. Resolve conflicts deliberately.
6. Run automated validation and a Local browser pass.
7. Open a PR from the sync branch into client `main`.
8. Review the PR like any other production change.
9. Merge the PR after validation passes.

```bash
git checkout main
git pull origin main
git fetch upstream
git checkout -b sync/platform-YYYY-MM-DD
git merge upstream/main
```

Merge conflicts are expected during sync. They mean both the platform and the
client touched the same contract area; they are not a sign that the sync failed.

### Conflict Example: `.gitignore`

If upstream adds platform ignores and the client adds project-specific ignores,
keep the union of both sets.

```gitignore
# Platform rules from upstream
/public/build
/vendor

# Client project rules
/.env.local
/client-export
```

Do not pick one side wholesale unless the rule is genuinely obsolete. For
`.gitignore`, the correct answer is usually "keep both".

### Files Most Likely To Conflict

| File | Why it conflicts | How to resolve |
| --- | --- | --- |
| `.gitignore` | Both platform and client add local tooling or generated-output rules. | Keep the union. Remove only duplicates or rules that are clearly wrong. |
| `resources/css/tokens.css` | Clients customize brand tokens while upstream evolves token contracts. | Preserve client values for existing tokens. Add new upstream tokens and aliases. Do not rename token contracts casually. |
| `resources/blocks/blocks-manifest.json` | Upstream adds platform blocks while clients add private blocks. | Keep both platform and client entries. Ensure each entry's folder exists and `category` matches `block.json`. |
| `app/blocks.php` | Upstream may evolve category, registration, or allowed-block behavior while clients add categories. | Preserve upstream registration logic and re-apply client categories or filters around it. Keep `sobe/*` hooks intact. |
| Client-modified tests | Upstream may broaden tests while clients adapt them for private blocks or local behavior. | Keep upstream coverage improvements and re-apply client-specific expectations narrowly. Run the full test suite after resolving. |

After resolving conflicts, inspect the diff before committing. A sync PR should
make it obvious which changes came from upstream and which conflict resolutions
were client-specific.

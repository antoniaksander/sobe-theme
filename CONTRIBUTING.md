# Sobe WP contributing

> This file is the binding architectural reference for every developer and AI agent working on this codebase. Every section is a decision, not a suggestion. Internal team members may also use `CLAUDE.md` for private AI-oriented context, but this file is the public source of truth.

---

## The Five Laws

This theme is built on the **Native Hybrid Monolith** pattern. Every capability layer has exactly one home. Every other location is forbidden.

1. **Editor experience is React/JSX only**, confined to `edit.jsx` inside the block folder. React never outputs frontend HTML.
2. **Frontend rendering is Blade only.** The `save()` function in every block always returns `null`. WordPress calls a PHP `render_callback` that delegates to a Blade view.
3. **WooCommerce integration prefers hooks first**, with template overrides allowed when hooks are insufficient for the required markup or layout change.
4. **Styling is the CSS token cascade.** No raw hex values outside `tokens.css`. No inline styles. No `style=""` attributes.
5. **The DOM is semantic.** Every HTML element has a reason to exist. No div soup. No wrapper elements added for styling convenience.

ACF is never used. Page builders are never used. The block inserter is curated to an allow-list. If you are about to break one of these laws, stop and find the correct location.

---

## Project Map

Critical paths only. `node_modules/`, `vendor/`, `public/build/`, and `.git/` are omitted.

```
sobe/
│
├── functions.php                   Boots Acorn (Laravel container). Add nothing here.
├── style.css                       Theme identity (Theme Name, Author, etc.)
├── vite.config.js                  Explicit entry points (no globbing), wordpressThemeJson plugin, bundle budget.
│
├── app/
│   ├── setup.php                   Explicit block registration ($custom_blocks array). Theme supports. Customizer.
│   ├── filters.php                 WordPress filter hooks only (nav, excerpt, SVG MIME, etc.)
│   ├── woocommerce.php             ALL WooCommerce integration. Hooks only. No exceptions.
│   ├── security.php                XML-RPC off, REST access control, wp_head cleanup.
│   └── View/
│       └── Composers/
│           ├── App.php             Global data injected into every view (logo, siteName).
│           ├── Post.php            Post-specific data (title, pagination).
│           └── Comments.php        Comment list data.
│
└── resources/
    ├── css/
    │   ├── tokens.css              Layer 1 — --c-* tokens + --wc-* aliases. Single source of truth.
    │   ├── app.css                 Layer 3 — @theme, @layer base/components/utilities.
    │   ├── woocommerce.css         WC overrides using only var(--wc-*). Conditionally enqueued.
    │   └── editor.css              Block editor mirror of tokens + .editor-styles-wrapper rules.
    │
    ├── js/
    │   ├── app.js                  Alpine.data('app'), Lenis, GSAP/ScrollTrigger, Animation Bus.
    │   ├── animations.js           initAnimationBus() + initStickyHeader(). Imported by app.js.
    │   └── editor.js               Block editor entry. Minimal — domReady wrapper only.
    │
    ├── blocks/
    │   └── {block-name}/           One folder per custom block. See SOP: Adding a New Block.
    │       ├── block.json
    │       ├── index.jsx
    │       ├── edit.jsx
    │       ├── save.jsx            Always returns null.
    │       ├── editor.scss         Editor chrome only. Usually empty.
    │       ├── style.scss          Shared front+editor. Usually empty (Tailwind-first).
    │       └── view.js             Optional. Only for block-specific frontend JS.
    │
    ├── patterns/
    │   └── {pattern-slug}.php      Native Gutenberg block patterns. See SOP: Block Patterns.
    │
    └── views/
        ├── layouts/
        │   └── app.blade.php       Master layout. x-data="app" on <html>. wp_head/wp_footer preserved.
        ├── sections/               Full-width regions: header-1/2/3, footer, checkout-header.
        ├── components/             Blade components: <x-button>, <x-section>, <x-side-cart>, etc.
        ├── partials/               Reusable includes: entry-meta, comments, page-header, etc.
        ├── blocks/                 Blade renderers for custom blocks. hero.blade.php, etc.
        └── woocommerce/            WooCommerce Blade wrappers and overrides.
```

> `resources/views/woocommerce/` is the home for WooCommerce Blade wrappers and overrides. Prefer hooks and filters where possible, but template overrides are permitted when WooCommerce does not expose a usable hook for the required markup or layout change.

> `resources/views/blocks/` file names must match the block slug exactly. `sobe/hero` → `hero.blade.php`.

---

## CSS & Token Architecture

### The Three-Layer Model

```
Layer 1  →  tokens.css         Raw values live here and ONLY here.
Layer 2  →  woocommerce.css    WC overrides. Reads var(--wc-*) exclusively.
Layer 3  →  app.css @theme     Translates --c-* into Tailwind utility classes.
```

The alias chain — follow it strictly and never skip a layer:

```css
/* tokens.css — Layer 1: the ONLY place a raw value is written */
--c-accent: #db2b39;
--wc-sale-badge-bg: var(--c-accent);   /* Layer 2 alias: never a raw value */

/* app.css @theme — Layer 3: generates bg-accent, text-accent, etc. */
--color-accent: var(--c-accent);

/* woocommerce.css — consumes the Layer 2 alias */
.wc-sale-badge { background-color: var(--wc-sale-badge-bg); }

/* Blade template — consumes the Layer 3 Tailwind utility */
<span class="bg-accent text-accent-fg">Sale!</span>
```

### Decision Table

| Situation                                                | Solution                                  | File                                          |
| -------------------------------------------------------- | ----------------------------------------- | --------------------------------------------- |
| Brand colour, font, or global design token               | `--c-*` custom property                   | `tokens.css`                                  |
| WooCommerce element colour/size                          | `--wc-*` aliasing a `--c-*`               | `tokens.css`                                  |
| Dark mode variant of a token                             | `.dark { --c-*: ...; }` block             | `tokens.css`                                  |
| Structural layout, spacing, sizing                       | Tailwind utility class                    | Blade template                                |
| GSAP scroll animation / WebGL canvas layer               | `@layer utilities` or `@layer components` | `app.css`                                     |
| CSS feature Tailwind cannot express (clip-path, counter) | `.wp-block-sobe-{name}` selector          | `editor.scss` or `style.scss` in block folder |
| Inline styles                                            | **Never**                                 | —                                             |

### Media Query Rule — CSS Range Syntax + `@theme` Variables

All media queries in this theme must use **CSS Media Queries Level 4 range syntax** and reference `@theme` breakpoint variables. Legacy `min-width`/`max-width` pairs and `.98px` fudge values are forbidden (AP-9).

```css
/* Correct */
@media (width < theme(--breakpoint-md))  { /* mobile */ }
@media (width >= theme(--breakpoint-md)) { /* tablet+ */ }
@media (theme(--breakpoint-md) <= width < theme(--breakpoint-lg)) { /* tablet only */ }
@media (width >= theme(--breakpoint-lg)) { /* desktop */ }

/* Forbidden */
@media (max-width: 767.98px) { ... }      /* legacy fudge */
@media (min-width: 768px) and (max-width: 1023.98px) { ... }  /* legacy range */
@media screen and (max-width: 782px) { ... }  /* unnecessary `screen and` qualifier */
```

The only permitted exception is the WordPress admin bar breakpoint (`782px`) which is an external WordPress constant, not a design token — document it with a comment.

### Known Bug: `--c-ring: inherit`

`tokens.css` currently sets `--c-ring: inherit` in both `:root` and `.dark`. `inherit` is not a colour value. It makes all focus rings and form focus glows invisible. **Fix before launch:** replace with a concrete token:

```css
/* tokens.css — fix required */
--c-ring: var(--c-accent); /* or a dedicated --c-focus-ring: #hex */
```

This also fixes `--wc-form-focus-ring-color`, which aliases `--c-ring`.

---

## SOP: Adding a New Block

### Step 1 — Create the folder

```
resources/blocks/your-block-name/
├── block.json
├── index.jsx
├── edit.jsx
└── save.jsx
```

Delete `view.js` if the block does not need frontend behavior. Keep it only for block-specific frontend interactivity such as sliders, filters, or animated UI that cannot live in shared app code. Do not create `editor.scss` or `style.scss` unless the block has a proven need for non-Tailwind CSS.

### Step 2 — Write `block.json`

Mandatory fields — copy this shape exactly:

```json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "sobe/your-block-name",
  "version": "0.1.0",
  "title": "Your Block Title",
  "category": "sobe-general",
  "description": "One sentence description.",
  "example": {},
  "supports": {
    "html": false
  },
  "textdomain": "sage",
  "attributes": {
    "yourAttribute": {
      "type": "string",
      "default": ""
    }
  }
}
```

| Field           | Rule                                                            |
| --------------- | --------------------------------------------------------------- |
| `apiVersion`    | Always `3`                                                      |
| `supports.html` | Always `false` — prevents Gutenberg from serialising block HTML |
| `category`      | Use a registered category such as `sobe-general`, `sobe-woocommerce`, or `sobe-content` |
| `textdomain`    | Always `sage` — never `woocommerce` or `default`                |
| `name`          | Always `sobe/{slug}` using kebab-case                           |

### Step 3 — Write `save.jsx`

Always and only:

```jsx
export default function save() {
  return null;
}
```

Never add logic here. This is a dynamic block. Blade handles all rendering.

### Step 4 — Write `edit.jsx`

The editor component has exactly two jobs:

1. Render a preview of the block so the editor author can see what they are building.
2. Wire up `setAttributes()` via `InspectorControls`.

```jsx
const { useBlockProps, InspectorControls } = wp.blockEditor;
const { PanelBody, TextControl } = wp.components;
const { __ } = wp.i18n;

export default function Edit({ attributes, setAttributes }) {
  const { heading } = attributes;
  const blockProps = useBlockProps();

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Content', 'sage')}>
          <TextControl
            label={__('Heading', 'sage')}
            value={heading}
            onChange={(val) => setAttributes({ heading: val })}
          />
        </PanelBody>
      </InspectorControls>
      <div {...blockProps}>
        <h2>{heading || __('Your heading here…', 'sage')}</h2>
      </div>
    </>
  );
}
```

**`edit.jsx` must never:** fetch data at render time, import jQuery or lodash, produce markup intended for the frontend, or use `useState` for data that belongs in `setAttributes`.

All `wp.*` globals are available without importing. Use them as shown above.

### WooCommerce customization rule

Prefer hooks and filters first. Use Blade WooCommerce template overrides only when hooks are insufficient for the required result.

Examples:

- Prefer hooks: reposition or inject elements in the single-product summary using hooks such as `woocommerce_single_product_summary` and hook priorities.
- Use a template override: when the required change restructures markup or layout in a way WooCommerce does not expose through hooks, such as a custom single-product wrapper structure or bespoke related-products markup.

When you add or change a WooCommerce template override, document the reason in the client repo because those files are upgrade-sensitive.

### Step 5 — Register in `setup.php`

Add the block slug to the `$custom_blocks` array at the top of the `init` hook:

```php
$custom_blocks = ['hero', 'your-block-name'];
```

The loop handles the rest. No other changes to `setup.php` are needed.

**Why `type="module"`:** The Vite-built bundle's minified variable names collide with `window._` (Underscore.js), breaking the Customizer. Module scope contains the collision. The `script_loader_tag` filter applies to all `sobe-*` handles automatically.

**Why no `lodash`/`underscore`/`jquery` in `$deps`:** These are non-module scripts. WordPress loads them before a module script executes, causing sequencing failures. Never add them (AP-7). Use `wp.*` globals instead.

### Step 6 — Add to Vite entry points

In `vite.config.js`, add the entry points explicitly to the `input` array:

```js
'resources/blocks/your-block-name/index.jsx',
// Add editor.scss and style.scss only if the block requires them:
// 'resources/blocks/your-block-name/editor.scss',
// 'resources/blocks/your-block-name/style.scss',
```

**No globbing. No auto-discovery. Every entry point is declared by hand.** This is intentional — the small cost of a manual edit prevents silent pathing errors and unpredictable builds.

### Step 7 — Create the Blade renderer

File: `resources/views/blocks/your-block-name.blade.php`

```blade
@php
  $heading    = $attributes['heading'] ?? '';
  $wrapperAttrs = get_block_wrapper_attributes(['class' => 'your-block-class']);
@endphp

<section {!! $wrapperAttrs !!} aria-label="{{ esc_attr($heading) }}">
  <h2 class="text-2xl font-heading">{!! wp_kses_post($heading) !!}</h2>
</section>
```

Rules:

- Always use `get_block_wrapper_attributes()` on the block root element — it merges Gutenberg colour panel classes, alignment classes, and your custom classes.
- Output `$wrapperAttrs` with `{!! !!}` — the function returns pre-escaped HTML. Escaping again would corrupt it.
- Output user-controlled string attributes with `{!! wp_kses_post($value) !!}` (see Security Baseline).
- Never hardcode a bare `class=` on the block root element.

### Step 8 — Create a Blade Composer for data-heavy blocks

If the block needs data beyond `$attributes` (e.g., a `WP_Query`), create a Composer. Never run queries inside a Blade template.

```php
// app/View/Composers/YourBlockName.php
namespace App\View\Composers;
use Roots\Acorn\View\Composer;

class YourBlockName extends Composer
{
    protected static $views = ['blocks.your-block-name'];

    public function posts(): array
    {
        return get_posts(['numberposts' => $this->view->attributes['count'] ?? 3]);
    }
}
```

Acorn auto-discovers Composers in `app/View/Composers/` — no manual registration needed.

### Step 9 — Block-specific CSS (when and when not)

**Do not create `style.scss`** if the block's visual treatment is entirely achievable with Tailwind utilities in the Blade template. This is true for most blocks.

**Create `style.scss`** only when:

- You need `clip-path`, scoped `@keyframes`, CSS `counter`, or another feature Tailwind v4 cannot express.
- The same rule must appear on both frontend and in the editor preview.

Then add it to the Vite `input` array. Never write Tailwind utility classes inside `style.scss`. Never import `tokens.css` in a block-level CSS file — the tokens are already globally available.

### Common mistakes

- Leaving the scaffolded `console.log("Hello World!")` stub in `view.js` — delete the stub or the file if the block does not need frontend JS (AP-6).
- Adding `lodash`, `underscore`, or `jquery` to `$deps` (AP-7).
- Forgetting the `type="module"` filter — block scripts will silently corrupt Underscore.js.
- Putting a `WP_Query` in the Blade template — move it to a Composer.
- Using `{!! $attributes['title'] !!}` without `wp_kses_post()` (AP-8/Security).
- Writing full class names via PHP string concatenation: `'text-' . $size` (AP-8 / Tailwind v4 rule).

---

## SOP: Modifying WooCommerce

### The Single File Rule

All WooCommerce integration lives in `app/woocommerce.php`. This means:

- Theme support declarations (`woocommerce`, `wc-product-gallery-*`)
- Hook additions and removals (`add_action`, `remove_action`, `add_filter`)
- Cart fragment AJAX responses
- AJAX action handlers (`wp_ajax_*`, `wp_ajax_nopriv_*`)
- Content wrapper overrides
- Conditional script enqueuing

If you are about to write WooCommerce logic outside this file — stop. Move it here.

### Hook manipulation belongs in PHP, never in templates

`remove_action()`, `add_action()`, and `add_filter()` are code, not template logic. They must live in PHP files that execute on every request, not in template files that run conditionally inside a loop.

**The specific anti-pattern found in this codebase:**

`content-product.blade.php` currently calls `remove_action('woocommerce_show_product_loop_sale_flash', ...)` inside the template loop. This is an AP-2 violation. The sale flash suppression must be moved to the `after_setup_theme` hook block in `woocommerce.php` alongside the other product card hook manipulations.

### Token architecture for WooCommerce styling

The rule is absolute:

1. Add selectors only to `woocommerce.css`.
2. Use only `var(--wc-*)` values inside `woocommerce.css`.
3. Define `--wc-*` properties only in `tokens.css`, as aliases of `--c-*`.
4. Never write a raw colour, raw pixel size, or raw `rgba()` inside `woocommerce.css`.
5. Never add a WooCommerce selector to `app.css`.

This separation keeps `woocommerce.css` conditionally enqueued (WC pages only), keeps `app.css` lean, and makes WC dark mode automatic — the `.dark` block in `tokens.css` redefines `--c-*` values, which cascade through `--wc-*` aliases, which cascade into `woocommerce.css` with zero additional work.

### When Blade template overrides are acceptable

Exactly three files are approved to exist in `resources/views/woocommerce/`:

- `archive-product.blade.php` — shop archive page wrapper
- `single-product.blade.php` — single product page wrapper
- `content-product.blade.php` — product card in archive loops

These exist only to wire WooCommerce's template loader into Blade's `@extends('layouts.app')` system. They delegate all actual product content rendering back to WooCommerce action hooks.

**Do not copy any other WooCommerce template into this directory.** Every additional file is a version-staleness liability. WooCommerce updates its core templates; your Blade copy stays pinned at the old version; bugs appear silently. WooCommerce's admin "outdated template" warning does not scan `.blade.php` files, so version drift will go undetected.

For every WooCommerce customisation, the answer is a hook in `woocommerce.php`.

### What never to do

- Copy `cart.php`, `checkout/form-checkout.php`, `single-product/add-to-cart/simple.php`, or any other WC template into `resources/views/woocommerce/`.
- Call `WC()->cart` or `WC()->session` from inside a Blade template (use a Composer or AJAX handler).
- Use `woocommerce_`-prefixed functions inside `resources/views/components/`.
- Add `class_exists('WooCommerce')` guards inside template files.

---

## SOP: Styling & CSS Tokens

Use this decision flowchart before reaching for any CSS file.

**1. Is this a brand colour, brand font, or a global design decision?**
→ Add or modify a `--c-*` property in `tokens.css`. Then check whether `app.css @theme` needs a matching `--color-*` entry, and whether `resources/scripts/build-theme-json.js` needs an `editorPalette` entry.

**2. Is this a visual property on a WooCommerce element?**
→ Does a `--wc-*` token exist? Use it. Does it not exist? Add `--wc-your-token: var(--c-something)` to `tokens.css`, then use `var(--wc-your-token)` in `woocommerce.css`.

**3. Is this a layout or structural property on a Blade template element?**
→ Write a Tailwind utility class directly in the Blade file.

**4. Is this a dark mode variant of an existing value?**
→ Add the override inside the `.dark { }` block in `tokens.css`.

**5. Is this a complex animation: GSAP tween target, WebGL canvas positioner, or scroll-driven CSS?**
→ Write it in `@layer utilities` or `@layer components` in `app.css`.

**6. Is this a pixel value that recurs in two or more places?**
→ It belongs in `@theme` in `app.css` as a new `--spacing-*`, `--radius-*`, or `--font-size-*` token.

**7. None of the above?**
→ Default: write a Tailwind utility class in the Blade template. Do not reach for a CSS file first.

### Tailwind v4 Static Class Rule

Tailwind v4 scans source files for class names as **static strings**. It cannot detect class names constructed via PHP string concatenation, ternary expressions, or `sprintf()`. Because this theme uses a CSS-first Tailwind v4 config, we do not safelist — **we write out the full static class name**.

```php
// Correct — Tailwind can scan the full strings
$heightClass = match($height) {
    'medium' => 'min-h-[70vh]',
    'large'  => 'min-h-[90vh]',
    'full'   => 'min-h-screen',
    default  => 'min-h-[80vh]',
};

// Forbidden — Tailwind cannot scan this (AP-8)
$heightClass = 'min-h-[' . $heightValue . 'vh]';
$sizeClass = 'text-' . $size;
$colClass = 'col-span-' . $columns;
```

This rule applies to Blade templates, Composers, `setup.php`, and `woocommerce.php` equally. If a PHP file builds a class string, Tailwind cannot see it. Write the full class name or restructure the logic so the Blade template contains the complete static string.

---

## SOP: Block Patterns

Native Gutenberg Block Patterns are the primary tool for giving clients instant, polished page layouts. A Pattern is a pre-configured group of blocks that the client can insert into any page from the block inserter. This is how design intent is delivered without locking clients into a custom page builder.

### When to create a Pattern

Create a Pattern whenever:

- A page section (hero + feature grid + CTA) is used on multiple pages.
- A layout has specific block configurations that are error-prone to recreate manually.
- The design team defines a "page template" that should be reproducible by a non-technical client.

### File structure

```
resources/patterns/
└── {pattern-slug}.php      One file per pattern.
```

Pattern files are PHP files that output the serialised block markup. Name the file after the pattern using kebab-case: `homepage-hero.php`, `product-feature-grid.php`, `contact-cta.php`.

### Registration in `setup.php`

Register a pattern category and individual patterns in an `init` hook:

```php
add_action('init', function () {
    // Register the Sobe pattern category (once, at theme level)
    register_block_pattern_category('sobe-patterns', [
        'label' => __('Sobe Layouts', 'sage'),
    ]);

    // Register individual patterns
    register_block_pattern('sobe/homepage-hero', [
        'title'       => __('Homepage Hero', 'sage'),
        'description' => __('Full-width hero with headline, subtitle, and CTA.', 'sage'),
        'categories'  => ['sobe-patterns'],
        'content'     => require resource_path('patterns/homepage-hero.php'),
    ]);
});
```

Alternatively, Gutenberg supports auto-loading patterns from `resources/patterns/` if the folder is registered as the patterns directory. Either approach is acceptable — pick one and document it in `setup.php`.

### Writing a pattern file

A pattern file must return (not echo) the serialised block comment markup:

```php
<?php
// resources/patterns/homepage-hero.php
return <<<PATTERN
<!-- wp:sobe/hero {"heading":"Your Headline Here","ctaText":"Get Started","ctaUrl":"#","height":"large"} /-->
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Supporting text goes here.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
PATTERN;
```

Generate the serialised markup by:

1. Building the layout in the block editor.
2. Switching to Code Editor view (⌘+Shift+Alt+M).
3. Copying the block comment markup.
4. Pasting it into the pattern file's `return <<<PATTERN` heredoc.

### Pattern rules

- Patterns must use only blocks on the `allowed_block_types_all` allow-list in `setup.php`.
- Patterns must include only `sobe/*` blocks or Core blocks — never third-party plugin blocks that may not be installed on all sites.
- Provide meaningful placeholder content (`heading`, `description`, `ctaText`) so the client sees a working preview, not empty fields.
- Version-pin patterns: when a block's attribute schema changes, update any patterns that reference it.
- Patterns are client-editable after insertion. They are starting points, not locked templates. Do not design patterns that depend on attributes staying at their default values.

### Client handoff note

After registering patterns, document them in a handoff note to the client: pattern name, purpose, and screenshot. The block inserter shows the pattern name and description — write both in plain language the client can understand.

---

## SOP: Deleting or Deprecating a Block

Never delete a block's code before confirming it is not used in the database.

### Step 1 — Search the database

```bash
wp db query "SELECT ID, post_title, post_type FROM wp_posts WHERE post_content LIKE '%<!-- wp:sobe/block-name%' AND post_status != 'auto-draft';"
```

If any posts contain the block, coordinate with the content team to replace or remove it before proceeding. The block will show a "block contains unexpected content" editor error until removed from the database.

Also check patterns:

```bash
grep -r 'sobe/block-name' resources/patterns/
```

### Step 2 — Remove registration from `setup.php`

Remove the block slug from the `$custom_blocks` array in the `init` hook. That is the only change needed in `setup.php`.

The `allowed_block_types_all` filter auto-discovers `sobe/*` blocks via the registry — no manual update is needed there.

### Step 3 — Remove from `vite.config.js`

Remove `'resources/blocks/block-name/index.jsx'` (and any associated `scss` entries) from the `input` array.

### Step 4 — Delete the file tree

```bash
rm -rf resources/blocks/block-name/
rm resources/views/blocks/block-name.blade.php
```

If the block had a dedicated Composer, delete `app/View/Composers/BlockName.php`.

If the block had a pattern, delete `resources/patterns/block-name-*.php` and remove the `register_block_pattern()` call from `setup.php`.

### Step 5 — Build and verify

```bash
npm run build
```

If the build fails with a missing import, a file elsewhere is still importing the deleted block. Trace and remove it.

### Step 6 — Post-delete checklist

- [ ] `npm run build` completes without error
- [ ] Deleted block handle does not appear in the browser Network tab on any page
- [ ] No PHP warnings in `debug.log` referencing the deleted view or Composer
- [ ] Database query from Step 1 returns zero results
- [ ] Pattern files referencing the deleted block have been removed
- [ ] The block category `sobe-blocks` is not empty (if it is, unregister it from `setup.php`)

---

## Anti-Pattern Registry

These patterns are explicitly forbidden. Each entry states what it is, where it has occurred in this codebase, and why it is harmful.

### AP-1: Template Override

**What:** Copying a WooCommerce core template into `resources/views/woocommerce/` beyond the three approved wrapper files.

**Why harmful:** WooCommerce updates its templates on every major release. Your copy stays pinned at the version it was copied from. WooCommerce's admin "outdated template" warning does not detect `.blade.php` files, so drift goes unnoticed. Layout breaks or security fixes in the WC template are silently missed.

**Correct approach:** All WC customisation goes in `app/woocommerce.php` as hooks. The three approved Blade wrappers exist only to bridge the template loader into Blade — they delegate all content to WC hooks.

---

### AP-2: Hook-in-Template

**What:** Calling `add_action()`, `remove_action()`, or `add_filter()` inside any file in `resources/views/`.

**Where it exists:** `resources/views/woocommerce/content-product.blade.php` currently calls `remove_action`/`add_action` for the sale flash inside the product loop. This must be moved to `woocommerce.php`.

**Why harmful:** Hook manipulation in templates is fragile (depends on the template executing exactly once in the expected order), untestable, and invisible to architectural review. Anyone auditing `woocommerce.php` gets an incomplete picture.

---

### AP-3: Invisible Class (Dynamic Tailwind in PHP)

**What:** Constructing a Tailwind class name via PHP string concatenation, ternary, or `sprintf()`.

**Example:** `'col-span-' . $columns` or `'text-' . $size` or `sprintf('bg-%s', $colour)`.

**Why harmful:** Tailwind v4 scans source files for class names as static strings. It cannot execute PHP. These class names appear to work in development (dev mode outputs the full Tailwind CSS) but are purged in production builds, causing invisible layout regressions.

**Correct approach:** Write out the full static class name in a `match` expression or conditional block. Tailwind can then find it as a literal string.

---

### AP-4: Broken Ring Token

**What:** Setting `--c-ring: inherit` in `tokens.css`.

**Where it exists:** Both `:root` and `.dark` in `tokens.css` currently set `--c-ring: inherit`.

**Why harmful:** `inherit` is not a colour. It makes `--c-ring` resolve to the element's `color` property, which produces an arbitrary and unpredictable ring colour — or effectively `transparent`. Every focus ring on the site and every WooCommerce form focus glow silently fails.

**Fix:** Replace with a concrete value: `--c-ring: var(--c-accent)` or a dedicated `--c-focus-ring` hex token.

---

### AP-5: Raw Value in `--wc-*`

**What:** Writing a raw hex colour, `rgb()`, or `rgba()` directly in a `--wc-*` property definition.

**Example:** `--wc-button-bg: #1a1a2e` instead of `--wc-button-bg: var(--c-primary)`.

**Why harmful:** The raw value bypasses the rebranding chain. Changing `--c-primary` in `tokens.css` will not update the WC button. In dark mode, the dark version of the raw value must be manually maintained instead of inherited automatically.

**Rule:** Every `--wc-*` property must alias a `--c-*` property. No exceptions.

---

### AP-6: Console.log Stub (`view.js`)

**What:** The `view.js` file generated by `@wordpress/create-block` scaffolding. It contains only `console.log("Hello World! (from sobe/{block-name} block)")`.

**Why harmful:** If the file is ever referenced (e.g., `viewScript` added to `block.json`) it ships console noise to every page load in production. Even unreferenced, it suggests false functionality and confuses future developers.

**Rule:** Delete the scaffolded stub immediately when scaffolding a new block. Only keep `view.js` if the block needs real frontend behaviour.

---

### AP-7: jQuery-in-Block-Editor

**What:** Adding `'jquery'`, `'lodash'`, or `'underscore'` to the `$deps` array of `wp_register_script()` for a block editor script.

**Where it exists:** The hero block originally had all three. The `type="module"` filter was added as a workaround for the resulting collision.

**Why harmful:** These are non-module global scripts. WordPress loads them before the module script, but module scripts execute in their own scope and cannot reliably access globals injected by non-module scripts. Adding them as deps can cause load-order conflicts, Customizer breakage, and undefined globals.

**Rule:** Block editor `$deps` must contain only `wp-*` handles: `wp-blocks`, `wp-element`, `wp-components`, `wp-i18n`, `wp-block-editor`. Access other utilities via `wp.*` globals in `edit.jsx`.

---

### AP-8: Unescaped Block Attribute

**What:** Rendering a user-controlled block attribute with `{!! $attribute !!}` without first sanitising through `wp_kses_post()`.

**Where it exists:** `resources/views/blocks/hero.blade.php` — `{!! $title !!}`, `{!! $description !!}`, and `{!! $ctaText !!}` are all output raw.

**Why harmful:** Block attributes of `type: "string"` are stored by Gutenberg without HTML escaping. An editor-role user can inject arbitrary markup. While the attack surface is limited to users with editor access, it is a stored XSS vector and should be eliminated.

**Rule:** Use `{!! wp_kses_post($value) !!}` for rich-text attributes (headings, descriptions). Use `{{ esc_html($value) }}` for plain-text attributes (labels, counts, alt text). See Security Baseline.

---

### AP-9: Legacy Breakpoint Syntax

**What:** Using `min-width`/`max-width` media query pairs, `.98px` fudge values, or the `screen and` qualifier.

**Example:** `@media (max-width: 767.98px)` or `@media screen and (max-width: 782px)`.

**Why harmful:** The `.98px` fudge is a Bootstrap 4 workaround for Safari/Chrome rounding bugs that no longer affect modern browsers (2020+). Using raw pixel values instead of `@theme` breakpoint variables means the media queries will silently drift out of sync if breakpoints change. The `screen and` qualifier is unnecessary noise.

**Rule:** All media queries must use CSS Range Syntax referencing `@theme` variables: `@media (width < theme(--breakpoint-md))`.

---

## Accessibility Baseline

Every new component, block renderer, section, and partial must meet these requirements before being merged.

**Skip link:** `app.blade.php` includes a skip-to-main-content link. Do not remove it. Do not wrap `<main id="main">` in a `<div>` — the anchor must be the target element.

**Focus rings:** Every interactive element must have a visible `:focus-visible` state. The global rule in `app.css @layer base` provides `outline: 2px solid var(--c-ring)`. Fix AP-4 (`--c-ring: inherit`) before launch. Never add `focus:outline-none` without a visible alternative.

**Landmark structure:** One `<main>`, one `<header>` (site header), one `<footer>` per page. `<section>` elements must have `aria-label` or a visible heading. The `<x-section>` component wraps in `<section>` automatically.

**Buttons vs links:** Elements that navigate are `<a href="...">`. Elements that trigger actions without navigation are `<button type="button">`. Do not use `<div>` or `<span>` with `@click` as interactive elements. The `<x-button>` component needs a `tag` prop to support `<button>` vs `<a>` — enforce the correct tag at the call site.

**Dialog/drawer pattern:** All modals, sheets, and drawers must replicate the pattern used in `side-cart.blade.php`: `role="dialog"`, `aria-modal="true"`, `aria-labelledby="{id of visible title}"`, `@keydown.escape.window`, and `x-trap.noscroll` for focus trapping. The mobile nav overlay in `header-1` and `header-2` does not yet implement this — it must be added.

**Images:** All `<img>` must have an `alt` attribute. Decorative images: `alt="" aria-hidden="true"`. Content images: descriptive `alt` text, ideally from WP attachment metadata.

**Icon-only buttons:** Must have `aria-label`. Decorative SVGs inside labelled buttons must have `aria-hidden="true"`. The side-cart close button and cart badge model this correctly.

**Reduced motion:** All GSAP animations must live inside a `gsap.matchMedia()` guard checking `prefers-reduced-motion: no-preference`. All CSS `transition` and `animation` declarations must have a `@media (prefers-reduced-motion: reduce)` counterpart that disables them.

**Colour contrast:** Verify `--c-text-muted` and `--c-text-subtle` against `--c-surface-1` using a contrast checker before launch. Both may fail WCAG AA (4.5:1 for body text).

**Live regions:** The cart count badge (`sobe-cart-count`) updates via Alpine `x-text` but is not wrapped in an `aria-live` region. Screen reader users are not informed when the cart count changes. Add an `aria-live="polite"` announcer region in `app.blade.php` and update it when `cartCount` changes.

---

## Security Baseline

### Blade escaping — the two-rule system

**Rule 1:** Use `{{ }}` for all user-controlled or database content. Blade's `{{ }}` calls `htmlspecialchars()` automatically.

**Rule 2:** Use `{!! !!}` only for content already sanitised by WordPress or the theme. Every use of `{!! !!}` requires a comment explaining why it is safe.

### When each is correct

```blade
{{-- Correct: user-controlled content, auto-escaped --}}
{{ $product->get_name() }}
{{ $siteName }}
{{ __('Text', 'sage') }}

{{-- Correct: WordPress function returns pre-escaped attribute string --}}
<div {!! $wrapperAttrs !!}>   {{-- get_block_wrapper_attributes() --}}

{{-- Correct: rich-text block attribute, sanitised to WP post content rules --}}
{!! wp_kses_post($title) !!}

{{-- Correct: WooCommerce returns internally escaped HTML --}}
{!! $product->get_image('woocommerce_thumbnail') !!}
{!! wc_price($unitPrice) !!}
{!! woocommerce_page_title(false) !!}

{{-- Wrong: raw block attribute, no sanitisation --}}
{!! $title !!}
{!! $description !!}

{{-- Wrong: $siteName from get_bloginfo() is not HTML-escaped --}}
{!! $siteName !!}
```

### The `wp_kses_post` rule

Any block attribute of `type: "string"` rendered without double-curly escaping must be wrapped in `wp_kses_post()`. This allows the same inline HTML permitted in post content (`<strong>`, `<em>`, `<a>`, `<br>`) while stripping `<script>`, `<iframe>`, and event attributes.

```blade
{{-- Rich text: allows <strong>, <em>, strips scripts --}}
<h2>{!! wp_kses_post($heading) !!}</h2>

{{-- Plain text: strips all HTML --}}
<figcaption>{{ esc_html($caption) }}</figcaption>

{{-- Inside an attribute: esc_attr() prevents attribute injection --}}
<img alt="{{ esc_attr($altText) }}">
```

### AJAX and REST

The `app/security.php` file blocks unauthenticated REST API access except for `/wc/` routes. Do not move REST access control to templates or Composers.

All `wp_ajax_*` handlers must verify a nonce via `check_ajax_referer()`. The `sobeCartParams.storeApiNonce` pattern (using `wp_create_nonce('wc_store_api')`) is correct for Store API requests. The `sobe_refresh_cart` handler currently lacks a nonce check — add one before launch.

### SVG uploads

SVGs are permitted via `upload_mimes` in `filters.php` but are not sanitised on upload. Add server-side SVG sanitisation (e.g., `enshrined/svg-sanitize`) before enabling SVG uploads in a production environment. Unsanitised SVGs can contain `<script>` tags.

---

## SOP: The Sobe Scalable Block Blueprint

This section is the codified standard derived from the hero block, ready to copy-paste for any new block. It covers patterns that the "SOP: Adding a New Block" steps above do not address in full: `supports`, InnerBlocks, viewScript, conditional controls, default sync, and i18n.

**Golden rule:** Prefer built-in `supports` over custom `InspectorControls` attributes. Add custom controls only for features Gutenberg does not provide natively.

**Textdomain:** All blocks use `'sage'` as the i18n text domain in both PHP and JS, matching `block.json`'s `"textdomain": "sage"`. The existing hero block uses `'sobe'` in `edit.jsx` — a legacy inconsistency that must be corrected before launch. All new blocks use `'sage'`.

---

### Rule 1 — Dynamic Rendering

`save.jsx` always returns `null`. This is a dynamic block. Blade handles all frontend HTML via the `render_callback` in `setup.php`. No exceptions.

```jsx
export default function save() {
  return null;
}
```

**Exception — InnerBlocks:** If the block uses `<InnerBlocks />` in `edit.jsx`, `save.jsx` must return `<InnerBlocks.Content />` wrapped in `useBlockProps.save()` so WordPress can persist the inner block tree. See the InnerBlocks note in Rule 4.

---

### Rule 2 — Explicit Wiring

A block is not active until it is registered in **both** places:

1. `app/setup.php` — add slug to the `$custom_blocks` array
2. `vite.config.js` — add `'resources/blocks/{slug}/index.jsx'` (and optional scss files) to the `input` array

No auto-discovery, no globbing. Both edits are made by hand on every new block.

---

### Rule 3 — Global Dependencies (Why `wp.*`, not `import '@wordpress/*'`)

In `edit.jsx` and `index.jsx`, **all `@wordpress/*` packages must be accessed as `wp.*` globals**, not ES6 imports.

```jsx
// ✓ Correct — reads from the global window.wp object
const { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck } =
  wp.blockEditor;
const {
  PanelBody,
  PanelRow,
  TextControl,
  SelectControl,
  ToggleControl,
  Button,
} = wp.components;
const { __ } = wp.i18n;

// For data queries in advanced blocks:
const { useSelect } = wp.data;
// useSelect((select) => select('core').getEntityRecords('postType', 'post', { per_page: 5 }), [])
```

```jsx
// ✗ Forbidden
import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
```

**Why:** The `wordpressPlugin()` from `@roots/vite-plugin` externalizes `@wordpress/*` packages — they are not bundled into the output. At runtime WordPress loads them as separate enqueued scripts that populate `window.wp.*`. The block's `$deps` array (`'wp-blocks'`, `'wp-element'`, `'wp-components'`, `'wp-i18n'`, `'wp-block-editor'`) in `setup.php` tells WordPress to enqueue those globals before the block module runs. An ES6 import attempts to resolve from `node_modules` and either bundles the package (bloat, version mismatch) or fails silently. Using `wp.*` accesses the live WordPress-registered copy.

**ES6 imports are only permitted for:**

- Local block files: `import Edit from './edit.jsx'`, `import metadata from './block.json'`
- Local stylesheets: `import './editor.scss'`, `import './style.scss'`

---

### Rule 4 — InspectorControls for Extra Settings

All client-facing settings live in `<InspectorControls>`, wired to `block.json` attributes. Never store settings in component state; never compute them at render time. Standard panel structure:

- **`Content` panel** (`initialOpen={true}`) — primary text fields and content inputs
- **`Extra Settings` panel** (`initialOpen={false}`) — layout options, toggles, display variants

All control `label` and `help` props must use `__(…, 'sage')`. All form controls (TextControl, SelectControl, ToggleControl) in WP 6.x must include `__nextHasNoMarginBottom`; TextControl and SelectControl also get `__next40pxDefaultSize`. These suppress deprecation warnings — see note below. Wrap each control in `<PanelRow>` for correct WP spacing. Do **not** pass `__next40pxDefaultSize` to `<Button>` — it is not a form control prop.

**Separate boolean toggles from their dependent content.** Never use the value of a string attribute as a toggle state. Use a dedicated boolean attribute so the author's text is preserved when the toggle is off:

```jsx
// ✓ Correct — showCta and ctaText are independent attributes
{ showCta && (
    <PanelRow>
        <TextControl
            label={ __('CTA Text', 'sage') }
            help={ __('Text shown on the button.', 'sage') }
            value={ ctaText }
            onChange={ (val) => setAttributes({ ctaText: val }) }
            placeholder={ __('Get started', 'sage') }
            __nextHasNoMarginBottom
            __next40pxDefaultSize
        />
    </PanelRow>
) }

// ✗ Wrong — toggling off permanently destroys the author's custom text
onChange={ (val) => setAttributes({ ctaText: val ? 'Get started' : '' }) }
```

**InnerBlocks** — if the block needs nested content (cards, feature grids):

```jsx
const { InnerBlocks } = wp.blockEditor;
// In Edit return: <InnerBlocks allowedBlocks={['core/heading', 'core/paragraph']} />
// In Blade: {!! $content !!}
```

When using InnerBlocks, `save.jsx` must return `<InnerBlocks.Content />`, not `null` (see Rule 1 exception).

---

### Rule 5 — Blade Data Handling

**Default sync — source of truth is `block.json`:**

```php
// Blade fallbacks must match block.json "default" values exactly
$heading     = $attributes['heading']     ?? '';       // "default": ""
$showCta     = $attributes['showCta']     ?? false;    // "default": false
$layout      = $attributes['layout']      ?? 'standard'; // "default": "standard"
```

A mismatch between `block.json` defaults and Blade fallbacks makes the editor and frontend disagree silently.

**Escaping matrix:**

| Context                            | Function                        | Example                                             |
| ---------------------------------- | ------------------------------- | --------------------------------------------------- |
| Rich text (headings, descriptions) | `wp_kses_post()` with `{!! !!}` | `{!! wp_kses_post($heading) !!}`                    |
| Plain text (labels, counts)        | `esc_html()` with `{{ }}`       | `{{ esc_html($caption) }}`                          |
| URL attributes                     | `esc_url()` with `{{ }}`        | `href="{{ esc_url($ctaUrl) }}"`                     |
| Inside HTML attributes             | `esc_attr()` with `{{ }}`       | `alt="{{ esc_attr($altText) }}"`                    |
| Pre-escaped WP function output     | `{!! !!}` only                  | `{!! $wrapperAttrs !!}`, `{!! wc_price($price) !!}` |

**Note on `wp_kses_post`:** Allows inline HTML permitted in post content (`<strong>`, `<em>`, `<a>`, `<br>`) while stripping `<script>`, `<iframe>`, and event attributes. If a field legitimately needs tags outside this allowlist, define a custom `wp_kses_allowed_html` array. Never use `{!! $attribute !!}` without a sanitiser.

---

### Rule 6 — Layout Wrappers

Use Gutenberg's built-in layout support instead of manually injecting layout classes.

**Standard content blocks** — declare `"layout": true` in `supports`. WordPress automatically adds `is-layout-constrained` (and the inner wrapper div) based on the parent layout context. The client's alignment controls in the sidebar work correctly.

```json
"supports": { "layout": true }
```

**Edge-to-edge blocks** (hero, full-bleed banners, full-screen sections) — declare `"layout": false` (or omit it) and manage your own width.

```json
"supports": { "layout": false }
```

Do **not** manually inject `is-layout-constrained max-w-none` via `get_block_wrapper_attributes()` on a block that also declares `supports.layout: true` — this creates CSS specificity conflicts and bypasses the user's alignment controls.

---

### Boilerplate: `block.json`

```json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "sobe/your-block-name",
  "version": "0.1.0",
  "title": "Your Block Title",
  "category": "sobe-blocks",
  "description": "One sentence description.",
  "example": {
    "attributes": {
      "heading": "Example Heading",
      "layout": "standard"
    }
  },
  "supports": {
    "html": false,
    "anchor": true,
    "className": true,
    "layout": true,
    "color": {
      "background": true,
      "text": true
    },
    "spacing": {
      "padding": true,
      "margin": true
    }
  },
  "textdomain": "sage",
  "attributes": {
    "heading": {
      "type": "string",
      "default": ""
    },
    "layout": {
      "type": "string",
      "enum": ["standard", "wide"],
      "default": "standard"
    },
    "showDivider": {
      "type": "boolean",
      "default": false
    },
    "showCta": {
      "type": "boolean",
      "default": false
    },
    "ctaText": {
      "type": "string",
      "default": ""
    },
    "ctaUrl": {
      "type": "string",
      "default": ""
    },
    "imageId": {
      "type": "number"
    },
    "imageUrl": {
      "type": "string",
      "default": ""
    }
  }
}
```

**Critical notes:**

- `"html": false` — always. Prevents Gutenberg from serialising block HTML.
- `"apiVersion": 3` — always. Changes how `useBlockProps` works and enables layout support.
- `"anchor": true` — free jump-link support at no cost.
- Do **not** add `editorScript`, `editorStyle`, `style`, or `viewScript` keys here. Assets are registered explicitly in `setup.php` via `\Roots\asset()` for Vite manifest integration. Adding them to `block.json` causes double-enqueueing.
- `"example"` attributes must match attributes defined in the `attributes` section. Use the same values as `"default"` so the editor preview renders correctly.
- `"align": ["wide", "full"]` — add to `supports` only if the block legitimately supports full-bleed alignment (most blocks do not need it).

---

### Boilerplate: `save.jsx`

**Standard dynamic block:**

```jsx
export default function save() {
  return null;
}
```

**Block with InnerBlocks:**

```jsx
const { useBlockProps, InnerBlocks } = wp.blockEditor;

export default function save() {
  return (
    <div {...useBlockProps.save()}>
      <InnerBlocks.Content />
    </div>
  );
}
```

---

### Boilerplate: `index.jsx`

```jsx
const { registerBlockType } = wp.blocks;

import metadata from './block.json';
import Edit from './edit.jsx';
import save from './save.jsx';

import './style.scss';

registerBlockType(metadata, {
  edit: Edit,
  save,
});
```

`import metadata from './block.json'` is a local file import (permitted). Passing the metadata object to `registerBlockType` is the apiVersion 3 pattern — it avoids duplicating the block name string and keeps attributes in sync automatically.

---

### Boilerplate: `edit.jsx` (full, copy-pasteable)

```jsx
// All @wordpress/* accessed via wp.* globals — never import from '@wordpress/…'
const { useBlockProps, InspectorControls, MediaUpload, MediaUploadCheck } =
  wp.blockEditor;
const {
  PanelBody,
  PanelRow,
  TextControl,
  SelectControl,
  ToggleControl,
  Button,
} = wp.components;
const { __ } = wp.i18n;

// Local imports only — block-scoped editor styles
import './editor.scss';

export default function Edit({ attributes, setAttributes }) {
  const {
    heading,
    layout,
    showDivider,
    showCta,
    ctaText,
    ctaUrl,
    imageId,
    imageUrl,
  } = attributes;
  const blockProps = useBlockProps();

  return (
    <>
      <InspectorControls>
        {/* Panel 1: Content — primary editing controls, open by default */}
        <PanelBody title={__('Content', 'sage')} initialOpen={true}>
          <PanelRow>
            <TextControl
              label={__('Heading', 'sage')}
              value={heading ?? ''}
              onChange={(val) => setAttributes({ heading: val })}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </PanelRow>
          <PanelRow>
            <ToggleControl
              label={__('Show CTA Button', 'sage')}
              checked={showCta}
              onChange={(val) => setAttributes({ showCta: val })}
              __nextHasNoMarginBottom
            />
          </PanelRow>
          {showCta && (
            <>
              <PanelRow>
                <TextControl
                  label={__('CTA Text', 'sage')}
                  help={__('Text shown on the button.', 'sage')}
                  value={ctaText}
                  onChange={(val) => setAttributes({ ctaText: val })}
                  placeholder={__('Get started', 'sage')}
                  __nextHasNoMarginBottom
                  __next40pxDefaultSize
                />
              </PanelRow>
              <PanelRow>
                <TextControl
                  label={__('CTA URL', 'sage')}
                  help={__('Include https:// for external links.', 'sage')}
                  value={ctaUrl ?? ''}
                  onChange={(val) => setAttributes({ ctaUrl: val })}
                  type="url"
                  placeholder="https://"
                  __nextHasNoMarginBottom
                  __next40pxDefaultSize
                />
              </PanelRow>
            </>
          )}
        </PanelBody>

        {/* Panel 2: Extra Settings — layout and display options, collapsed by default */}
        <PanelBody title={__('Extra Settings', 'sage')} initialOpen={false}>
          <PanelRow>
            <SelectControl
              label={__('Layout', 'sage')}
              value={layout}
              options={[
                { label: __('Standard', 'sage'), value: 'standard' },
                { label: __('Wide', 'sage'), value: 'wide' },
              ]}
              onChange={(val) => setAttributes({ layout: val })}
              __nextHasNoMarginBottom
              __next40pxDefaultSize
            />
          </PanelRow>
          <PanelRow>
            <ToggleControl
              label={__('Show Divider', 'sage')}
              checked={showDivider}
              onChange={(val) => setAttributes({ showDivider: val })}
              __nextHasNoMarginBottom
            />
          </PanelRow>
          <PanelRow>
            <MediaUploadCheck>
              <MediaUpload
                onSelect={(media) =>
                  setAttributes({ imageId: media.id, imageUrl: media.url })
                }
                allowedTypes={['image']}
                value={imageId}
                render={({ open }) =>
                  imageUrl ? (
                    <>
                      <img
                        src={imageUrl}
                        alt=""
                        style={{ maxWidth: '100%', marginBottom: '8px' }}
                      />
                      <Button variant="secondary" onClick={open}>
                        {__('Change Image', 'sage')}
                      </Button>
                      <Button
                        variant="link"
                        isDestructive
                        onClick={() =>
                          setAttributes({ imageId: null, imageUrl: '' })
                        }
                      >
                        {__('Remove Image', 'sage')}
                      </Button>
                    </>
                  ) : (
                    <Button variant="secondary" onClick={open}>
                      {__('Select Image', 'sage')}
                    </Button>
                  )
                }
              />
            </MediaUploadCheck>
          </PanelRow>
        </PanelBody>
      </InspectorControls>

      {/* Editor canvas — preview of the block for the author */}
      <div {...blockProps}>
        <h2>{heading || __('Your heading here…', 'sage')}</h2>
      </div>
    </>
  );
}
```

**`__nextHasNoMarginBottom` / `__next40pxDefaultSize` note:** These props suppress WP 6.4–6.5 deprecation warnings on form controls. They opt into spacing/size behaviour that will become the default in a future WP version. Remove them if/when WordPress removes the prop (the deprecation warning will tell you). Do **not** pass them to `<Button>` — it is not a form control.

---

### Boilerplate: Blade renderer

**Standard block** (content constrained by parent layout via `supports.layout: true`):

```blade
@php
  $heading     = $attributes['heading']     ?? '';
  $showDivider = $attributes['showDivider'] ?? false;
  $showCta     = $attributes['showCta']     ?? false;
  $ctaText     = $attributes['ctaText']     ?? '';
  $ctaUrl      = $attributes['ctaUrl']      ?? '#';
  $imageId     = $attributes['imageId']     ?? null;

  $wrapperAttrs = get_block_wrapper_attributes(['class' => 'my-block']);
@endphp

<section {!! $wrapperAttrs !!} aria-label="{{ esc_attr($heading) }}">
  @if($imageId)
    <img
      src="{{ esc_url(wp_get_attachment_image_url($imageId, 'full')) }}"
      alt="{{ esc_attr(get_post_meta($imageId, '_wp_attachment_image_alt', true)) }}"
    >
  @endif

  @if($showDivider)
    <hr class="border-t border-border mb-8" aria-hidden="true">
  @endif

  <h2>{!! wp_kses_post($heading) !!}</h2>

  @if($showCta && $ctaText)
    {{-- Replace with your theme's button component (e.g. <x-button>) if available --}}
    <a href="{{ esc_url($ctaUrl) }}" class="btn btn-dark">
      {!! wp_kses_post($ctaText) !!}
    </a>
  @endif
</section>
```

**Edge-to-edge block** (hero, full-bleed sections — `supports.layout: false`):

```blade
@php
  $heading      = $attributes['heading'] ?? '';
  $wrapperAttrs = get_block_wrapper_attributes([
    'class'        => 'relative w-full min-h-screen flex items-center overflow-hidden',
    'data-animate' => 'my-block',
  ]);
@endphp

<section {!! $wrapperAttrs !!}>
  <div class="relative z-10 px-6 lg:px-16 w-full">
    <h1 class="font-bold">{!! wp_kses_post($heading) !!}</h1>
  </div>
</section>
```

**Rule:** Always call `get_block_wrapper_attributes()` on the root element — it merges Gutenberg colour panel classes, alignment classes, and anchor IDs. Output with `{!! !!}` (pre-escaped; double-escaping corrupts the markup).

---

### Attribute Type → Control Quick Reference

| Attribute                      | `block.json` type                 | Recommended control                                                     |
| ------------------------------ | --------------------------------- | ----------------------------------------------------------------------- |
| Short text                     | `string`                          | `TextControl`                                                           |
| Long text / description        | `string`                          | `TextareaControl`                                                       |
| One-of fixed choices           | `string` + `enum`                 | `SelectControl`                                                         |
| Boolean on/off                 | `boolean`                         | `ToggleControl`                                                         |
| Conditional field (text)       | `boolean` toggle + `string` value | Separate attributes; toggle hides/shows `TextControl`                   |
| URL                            | `string`                          | `TextControl` with `type="url"`                                         |
| Image                          | `number` (ID) + `string` (URL)    | `MediaUpload` + `MediaUploadCheck`; always show preview + Remove button |
| Color (outside WP color panel) | `string`                          | `ColorPalette`                                                          |
| Nested content                 | —                                 | `InnerBlocks`; `save.jsx` returns `<InnerBlocks.Content />`             |
| Dynamic data (posts, terms)    | —                                 | `useSelect` from `wp.data` in `edit.jsx`                                |

---

### Frontend JS for interactive blocks

If a block requires frontend JavaScript (carousel, accordion, map init):

1. Create `resources/blocks/your-block/view.js`
2. Add `'resources/blocks/your-block/view.js'` to the Vite `input` array
3. Register it in `setup.php` using the Vite manifest pattern — do **not** add `viewScript` to `block.json`

```php
// Inside the $custom_blocks loop — conditional guard prevents broken handles
// on blocks that don't have a view.js.
$view_path = resource_path('blocks/' . $block_slug . '/view.js');
$block_args = [
    'editor_script'   => 'sobe-' . $block_slug,
    'render_callback' => function ($attributes, $content = '') use ($block_slug) {
        return view('blocks.' . $block_slug, compact('attributes', 'content'))->render();
    },
];

if (file_exists($view_path)) {
    $view_uri = \Roots\asset('resources/blocks/' . $block_slug . '/view.js')->uri();
    wp_register_script('sobe-' . $block_slug . '-view', $view_uri, [], null, true);
    $block_args['view_script'] = 'sobe-' . $block_slug . '-view';
}

register_block_type(resource_path('blocks/' . $block_slug), $block_args);
```

**Why not `"viewScript": "file:./view.js"` in `block.json`:** WordPress resolves `file:` paths relative to the block directory at registration time, but Vite's production build hashes filenames (`view-abc123.js`) and outputs to `public/build/`. The path never matches. Registering via `\Roots\asset()` reads the manifest and resolves the correct hashed URL — the same pattern used for `editor_script`.

A `view.js` with real frontend behaviour is correct and expected. What AP-6 forbids is the scaffolded stub containing only `console.log("Hello World!")`.

---

### render_callback — `$block` third parameter

The render callback accepts three arguments. The current `setup.php` loop uses a two-argument closure. Adding `$block` is a **non-breaking change** — PHP ignores extra parameters; existing blocks unaffected. Only update the closure when a specific block needs `$block->context` (e.g., query loop context) or `$block->name`.

```php
'render_callback' => function ($attributes, $content, $block) use ($block_slug) {
    return view('blocks.' . $block_slug, [
        'attributes' => $attributes,
        'content'    => $content,
        'block'      => $block,  // WP_Block: ->name, ->context, ->parsed_block
    ])->render();
},
```

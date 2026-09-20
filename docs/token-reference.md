# Token Reference

Canonical source: `resources/css/tokens.css`. Update this reference whenever the
platform token contract changes.

Client forks should override existing token values in
`resources/css/client-tokens.css`. Do not edit `resources/css/tokens.css` in a
client fork.

## Fonts

| Token | Controls | Platform default |
| --- | --- | --- |
| `--font-sans` | Default sans font stack | `'Satoshi', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif` |
| `--font-heading` | Heading and display font stack | `'CabinetGrotesk', var(--font-sans)` |
| `--font-serif` | Serif font stack | `ui-serif, Georgia, Cambria, 'Times New Roman', serif` |
| `--font-mono` | Monospace font stack | `ui-monospace, 'Cascadia Code', 'Fira Code', 'Consolas', monospace` |

If adding a new font, register the `@font-face` in `app/setup-patterns.php`,
place the font file in `fonts/`, and then point the relevant font token at the
new family.

## Light Colors

| Token | Controls | Platform default |
| --- | --- | --- |
| `--c-background` | Page background | `#ffffff` |
| `--c-surface-1` | Primary surfaces | `#ffffff` |
| `--c-surface-2` | Secondary surfaces | `#f8fafc` |
| `--c-surface-3` | Tertiary surfaces | `#f1f5f9` |
| `--c-surface-invert` | Inverted surface background | `#111827` |
| `--c-surface-invert-fg` | Text on inverted surfaces | `#ffffff` |
| `--c-text` | Body text | `#111827` |
| `--c-text-muted` | Muted text | `#64748b` |
| `--c-text-subtle` | Subtle text | `#94a3b8` |
| `--c-heading` | Heading text | `#030712` |
| `--c-border` | Borders and dividers | `#e5e7eb` |
| `--c-ring` | Focus ring color | `var(--c-accent)` |
| `--c-overlay` | Strong overlay | `rgba(0, 0, 0, 0.5)` |
| `--c-overlay-mid` | Medium overlay | `rgba(0, 0, 0, 0.4)` |
| `--c-overlay-light-low` | Low white overlay for glass/photo surfaces | `rgba(255, 255, 255, 0.1)` |
| `--c-overlay-light-mid` | Medium white overlay for glass/photo surfaces | `rgba(255, 255, 255, 0.3)` |
| `--c-overlay-light-high` | High white overlay for glass/photo surfaces | `rgba(255, 255, 255, 0.6)` |
| `--c-overlay-light-strong` | Strong white overlay for glass/photo surfaces | `rgba(255, 255, 255, 0.85)` |
| `--c-overlay-light-full` | Full white overlay | `#ffffff` |
| `--c-primary` | Primary brand color | `#111827` |
| `--c-primary-fg` | Text on primary color | `#ffffff` |
| `--c-primary-hover` | Primary hover color | `color-mix(in oklch, var(--c-primary) 82%, white)` |
| `--c-accent` | Accent brand color | `#4b5563` |
| `--c-accent-fg` | Text on accent color | `#ffffff` |
| `--c-accent-hover` | Accent hover color | `color-mix(in oklch, var(--c-accent) 82%, black)` |
| `--c-stars` | Rating star color | `#f59e0b` |
| `--c-sale` | Sale/discount color | `#dc2626` |
| `--c-btn-navy` | Dark button alias | `var(--c-primary)` |
| `--c-btn-navy-hover` | Dark button hover alias | `color-mix(in oklch, var(--c-btn-navy) 82%, white)` |
| `--c-btn-cream` | Light button alias | `var(--c-background)` |
| `--c-btn-cream-hover` | Light button hover alias | `color-mix(in oklch, var(--c-btn-cream) 92%, black)` |
| `--selection-bg` | Text selection background | `var(--c-text)` |
| `--selection-fg` | Text selection foreground | `var(--color-background)` |
| `--c-surface-raised` | Raised UI surfaces | `var(--c-surface-2)` |
| `--c-surface-hover` | Generic surface hover background | `rgba(0, 0, 0, 0.04)` |
| `--c-primary-subtle` | Subtle primary tint | `rgba(17, 24, 39, 0.08)` |

## Dark Colors

| Token | Controls | Platform default |
| --- | --- | --- |
| `--c-background` | Dark page background | `#0f172a` |
| `--c-surface-1` | Dark primary surfaces | `#111827` |
| `--c-surface-2` | Dark secondary surfaces | `#1f2937` |
| `--c-surface-3` | Dark tertiary surfaces | `#334155` |
| `--c-surface-invert` | Dark-mode inverted surface background | `#f8fafc` |
| `--c-surface-invert-fg` | Text on dark-mode inverted surfaces | `#111827` |
| `--c-text` | Dark body text | `#e5e7eb` |
| `--c-text-muted` | Dark muted text | `#94a3b8` |
| `--c-text-subtle` | Dark subtle text | `#64748b` |
| `--c-heading` | Dark heading text | `#f8fafc` |
| `--c-border` | Dark borders and dividers | `#334155` |
| `--c-ring` | Dark focus ring color | `var(--c-accent)` |
| `--c-overlay` | Dark strong overlay | `rgba(0, 0, 0, 0.7)` |
| `--c-primary` | Dark primary brand color | `#f8fafc` |
| `--c-primary-fg` | Text on dark primary color | `#111827` |
| `--c-primary-hover` | Dark primary hover color | `color-mix(in oklch, var(--c-primary) 92%, black)` |
| `--c-accent` | Dark accent brand color | `#cbd5e1` |
| `--c-accent-fg` | Text on dark accent color | `#111827` |
| `--c-accent-hover` | Dark accent hover color | `color-mix(in oklch, var(--c-accent) 82%, white)` |
| `--c-stars` | Dark rating star color | `#fbbf24` |
| `--c-sale` | Dark sale/discount color | `#f87171` |
| `--selection-bg` | Dark text selection background | `var(--c-text)` |
| `--selection-fg` | Dark text selection foreground | `var(--color-background)` |
| `--c-surface-raised` | Dark raised UI surfaces | `#1f2937` |
| `--c-surface-hover` | Dark generic surface hover background | `rgba(255, 255, 255, 0.06)` |
| `--c-primary-subtle` | Dark subtle primary tint | `rgba(248, 250, 252, 0.08)` |

## WooCommerce Aliases

| Token | Controls | Platform default |
| --- | --- | --- |
| `--wc-surface-bg` | WooCommerce surface background | `var(--c-surface-1)` |
| `--wc-surface-border` | WooCommerce surface border | `var(--c-border)` |
| `--wc-card-shadow-hover` | Product card hover shadow | `0 4px 16px rgba(0, 0, 0, 0.08)` |
| `--wc-product-card-aspect-ratio` | Product card image ratio | `1 / 1` |
| `--wc-product-card-title-color` | Product card title color | `var(--c-heading)` |
| `--wc-product-card-title-size` | Product card title size | `1rem` |
| `--wc-product-card-brand-color` | Product card brand label color | `var(--c-text-muted)` |
| `--wc-product-archive-title-color` | Product archive title color | `var(--c-heading)` |
| `--wc-product-archive-title-size` | Product archive title size | `clamp(2rem, calc(1.824rem + 0.751vw), 2.5rem)` |
| `--wc-product-single-title-color` | Product page title color | `var(--c-heading)` |
| `--wc-product-single-title-size` | Product page title size | `clamp(1.5rem, calc(2.148rem + 1.502vw), 3.5rem)` |
| `--wc-price-regular-color` | Regular price color | `var(--c-text)` |
| `--wc-price-sale-color` | Sale price color | `var(--c-sale)` |
| `--wc-price-compare-color` | Compare-at price color | `var(--c-text-subtle)` |
| `--wc-price-size` | Product card price size | `0.9375rem` |
| `--wc-price-single-size` | Product page price size | `1.5rem` |
| `--wc-rating-stars-color` | WooCommerce star rating color | `var(--c-stars)` |
| `--wc-sale-badge-bg` | Sale badge background | `var(--c-accent)` |
| `--wc-sale-badge-fg` | Sale badge text | `var(--c-accent-fg)` |
| `--wc-button-bg` | Primary WooCommerce button background | `var(--c-primary)` |
| `--wc-button-fg` | Primary WooCommerce button text | `var(--c-primary-fg)` |
| `--wc-button-hover-bg` | Primary WooCommerce button hover background | `var(--c-primary-hover)` |
| `--wc-button-hover-fg` | Primary WooCommerce button hover text | `var(--c-primary-fg)` |
| `--wc-button-alt-bg` | Alternate WooCommerce button background | `var(--c-accent)` |
| `--wc-button-alt-fg` | Alternate WooCommerce button text | `var(--c-accent-fg)` |
| `--wc-button-alt-hover-bg` | Alternate WooCommerce button hover background | `var(--c-accent-hover)` |
| `--wc-button-alt-hover-fg` | Alternate WooCommerce button hover text | `var(--c-accent-fg)` |
| `--wc-add-to-cart-sign-color` | Add-to-cart sign/icon color | `var(--wc-button-fg)` |
| `--wc-add-to-cart-sign-hover-color` | Add-to-cart sign/icon hover color | `var(--wc-button-hover-fg)` |
| `--wc-result-count-color` | Result count text | `var(--c-text-muted)` |
| `--wc-short-description-color` | Product short description text | `var(--c-text-muted)` |
| `--wc-ordering-select-bg` | Catalog ordering select background | `var(--c-surface-1)` |
| `--wc-ordering-select-fg` | Catalog ordering select text | `var(--c-text)` |
| `--wc-ordering-select-border` | Catalog ordering select border | `var(--c-border)` |
| `--wc-form-label-color` | WooCommerce form labels | `var(--c-text)` |
| `--wc-form-input-bg` | WooCommerce input background | `var(--c-surface-1)` |
| `--wc-form-input-fg` | WooCommerce input text | `var(--c-text)` |
| `--wc-form-border-color` | WooCommerce input border | `var(--c-border)` |
| `--wc-form-focus-ring-color` | WooCommerce input focus ring | `var(--c-ring)` |
| `--wc-table-heading-color` | WooCommerce table headings | `var(--c-text-muted)` |
| `--wc-table-cell-color` | WooCommerce table cell text | `var(--c-text)` |
| `--wc-table-border-color` | WooCommerce table borders | `var(--c-border)` |
| `--wc-table-link-color` | WooCommerce table links | `var(--c-heading)` |
| `--wc-table-link-hover-color` | WooCommerce table link hover | `var(--c-accent)` |
| `--wc-notice-bg` | WooCommerce notice background | `var(--c-surface-1)` |
| `--wc-notice-fg` | WooCommerce notice text | `var(--c-text)` |
| `--wc-notice-accent` | WooCommerce notice accent | `var(--c-accent)` |

Dark WooCommerce defaults:

| Token | Controls | Platform default |
| --- | --- | --- |
| `--wc-surface-bg` | Dark WooCommerce surface background | `var(--c-surface-1)` |
| `--wc-surface-border` | Dark WooCommerce surface border | `var(--c-border)` |
| `--wc-card-shadow-hover` | Dark product card hover shadow | `0 4px 16px rgba(0, 0, 0, 0.28)` |
| `--wc-product-card-title-color` | Dark product card title color | `var(--c-heading)` |
| `--wc-product-card-title-size` | Dark product card title size | `1rem` |
| `--wc-product-card-brand-color` | Dark product card brand label color | `var(--c-text-muted)` |
| `--wc-product-archive-title-color` | Dark product archive title color | `var(--c-heading)` |
| `--wc-product-archive-title-size` | Dark product archive title size | `clamp(2rem, calc(1.824rem + 0.751vw), 2.5rem)` |
| `--wc-product-single-title-color` | Dark product page title color | `var(--c-heading)` |
| `--wc-product-single-title-size` | Dark product page title size | `clamp(2.5rem, calc(2.148rem + 1.502vw), 3.5rem)` |
| `--wc-price-regular-color` | Dark regular price color | `var(--c-text)` |
| `--wc-price-sale-color` | Dark sale price color | `var(--c-accent)` |
| `--wc-price-compare-color` | Dark compare-at price color | `var(--c-text-subtle)` |
| `--wc-price-size` | Dark product card price size | `0.9375rem` |
| `--wc-price-single-size` | Dark product page price size | `1.5rem` |
| `--wc-rating-stars-color` | Dark WooCommerce star rating color | `var(--c-stars)` |
| `--wc-sale-badge-bg` | Dark sale badge background | `var(--c-accent)` |
| `--wc-sale-badge-fg` | Dark sale badge text | `var(--c-accent-fg)` |
| `--wc-button-bg` | Dark primary WooCommerce button background | `var(--c-primary)` |
| `--wc-button-fg` | Dark primary WooCommerce button text | `var(--c-primary-fg)` |
| `--wc-button-hover-bg` | Dark primary WooCommerce button hover background | `var(--c-primary-hover)` |
| `--wc-button-hover-fg` | Dark primary WooCommerce button hover text | `var(--c-primary-fg)` |
| `--wc-button-alt-bg` | Dark alternate WooCommerce button background | `var(--c-accent)` |
| `--wc-button-alt-fg` | Dark alternate WooCommerce button text | `var(--c-accent-fg)` |
| `--wc-button-alt-hover-bg` | Dark alternate WooCommerce button hover background | `var(--c-accent-hover)` |
| `--wc-button-alt-hover-fg` | Dark alternate WooCommerce button hover text | `var(--c-accent-fg)` |
| `--wc-add-to-cart-sign-color` | Dark add-to-cart sign/icon color | `var(--wc-button-fg)` |
| `--wc-add-to-cart-sign-hover-color` | Dark add-to-cart sign/icon hover color | `var(--wc-button-hover-fg)` |
| `--wc-result-count-color` | Dark result count text | `var(--c-text-muted)` |
| `--wc-short-description-color` | Dark product short description text | `var(--c-text-muted)` |
| `--wc-ordering-select-bg` | Dark catalog ordering select background | `var(--c-surface-1)` |
| `--wc-ordering-select-fg` | Dark catalog ordering select text | `var(--c-text)` |
| `--wc-ordering-select-border` | Dark catalog ordering select border | `var(--c-border)` |
| `--wc-form-label-color` | Dark WooCommerce form labels | `var(--c-text)` |
| `--wc-form-input-bg` | Dark WooCommerce input background | `var(--c-surface-1)` |
| `--wc-form-input-fg` | Dark WooCommerce input text | `var(--c-text)` |
| `--wc-form-border-color` | Dark WooCommerce input border | `var(--c-border)` |
| `--wc-form-focus-ring-color` | Dark WooCommerce input focus ring | `var(--c-ring)` |
| `--wc-table-heading-color` | Dark WooCommerce table headings | `var(--c-text-muted)` |
| `--wc-table-cell-color` | Dark WooCommerce table cell text | `var(--c-text)` |
| `--wc-table-border-color` | Dark WooCommerce table borders | `var(--c-border)` |
| `--wc-table-link-color` | Dark WooCommerce table links | `var(--c-heading)` |
| `--wc-table-link-hover-color` | Dark WooCommerce table link hover | `var(--c-accent)` |
| `--wc-notice-bg` | Dark WooCommerce notice background | `var(--c-surface-1)` |
| `--wc-notice-fg` | Dark WooCommerce notice text | `var(--c-text)` |
| `--wc-notice-accent` | Dark WooCommerce notice accent | `var(--c-accent)` |

## Product Category Grid

| Token | Controls | Platform default |
| --- | --- | --- |
| `--sobe-cat-grid-gap` | Category grid gap | `var(--space-md)` |
| `--sobe-cat-grid-radius` | Category grid tile radius | `var(--radius-xl)` |
| `--sobe-cat-grid-min-cell` | Category grid minimum cell width | `clamp(7.5rem, 18vw, 11rem)` |
| `--sobe-cat-grid-zoom-hover` | Category grid hover zoom | `1.08` |
| `--sobe-cat-grid-section-pt` | Category grid section top padding | `2rem` |
| `--sobe-cat-grid-section-pb` | Category grid section bottom padding | `2rem` |
| `--sobe-cat-grid-mobile-slide-aspect` | Mobile category slide aspect ratio | `3 / 4` |

## Layout

| Token | Controls | Platform default |
| --- | --- | --- |
| `--layout-content` | Gutenberg content column | `72rem` |
| `--layout-wide` | Gutenberg wide column | `90rem` |
| `--layout-reading` | Prose/article max width | `60rem` |
| `--layout-standard` | Default page container width | `90rem` |
| `--layout-grid` | Full product grid outer width | `96rem` |
| `--cq-product-card-compact` | Product card compact container-query width | `180px` |

Usable in templates as Tailwind utilities (`max-w-reading`, `max-w-content`,
`max-w-standard`, `max-w-grid`) or via `<x-section width="…">`
(`reading` | `content` | `standard` | `grid`). `--layout-content` (`72rem`)
is also what WordPress core blocks use automatically for the unconstrained
"none" alignment, via `theme.json`'s `settings.layout.contentSize` — the
`content` Tailwind/`x-section` option gives custom blocks and templates the
same width without hardcoding the value.

## Spacing

| Token | Controls | Platform default |
| --- | --- | --- |
| `--space-xs` | Extra-small spacing | `clamp(0.25rem, calc(0.206rem + 0.188vw), 0.375rem)` |
| `--space-sm` | Small spacing | `clamp(0.5rem, calc(0.412rem + 0.376vw), 0.75rem)` |
| `--space-md` | Medium spacing | `clamp(0.75rem, calc(0.618rem + 0.564vw), 1.125rem)` |
| `--space-lg` | Large spacing | `clamp(1rem, calc(0.824rem + 0.751vw), 1.5rem)` |
| `--space-xl` | Extra-large spacing | `clamp(1.5rem, calc(1.148rem + 1.502vw), 2.5rem)` |
| `--space-2xl` | 2XL spacing | `clamp(2.5rem, calc(1.972rem + 2.254vw), 4rem)` |
| `--space-3xl` | 3XL spacing | `clamp(4rem, calc(2.768rem + 5.258vw), 7.5rem)` |

## Type Scale

| Token | Controls | Platform default |
| --- | --- | --- |
| `--text-xs` | Extra-small text | `0.75rem` |
| `--text-sm` | Small text | `0.875rem` |
| `--text-base` | Base text | `1rem` |
| `--text-lg` | Large text | `clamp(1rem, calc(0.965rem + 0.15vw), 1.125rem)` |
| `--text-xl` | XL text | `clamp(1.125rem, calc(1.036rem + 0.376vw), 1.375rem)` |
| `--text-2xl` | 2XL text | `clamp(1.25rem, calc(1.118rem + 0.564vw), 1.625rem)` |
| `--text-3xl` | 3XL text | `clamp(1.5rem, calc(1.324rem + 0.751vw), 2rem)` |
| `--text-4xl` | 4XL text | `clamp(1.75rem, calc(1.486rem + 1.127vw), 2.5rem)` |
| `--text-5xl` | 5XL text | `clamp(2rem, calc(1.648rem + 1.502vw), 3rem)` |
| `--text-6xl` | 6XL text | `clamp(2.5rem, calc(1.972rem + 2.254vw), 4rem)` |
| `--text-7xl` | 7XL text | `clamp(3rem, calc(2.12rem + 3.756vw), 5.5rem)` |

## Type Details

| Token | Controls | Platform default |
| --- | --- | --- |
| `--font-normal` | Normal weight | `400` |
| `--font-medium` | Medium weight | `500` |
| `--font-semibold` | Semibold weight | `600` |
| `--font-bold` | Bold weight | `700` |
| `--tracking-tight` | Tight letter spacing | `-0.025em` |
| `--tracking-normal` | Normal letter spacing | `0` |
| `--tracking-wide` | Wide letter spacing | `0.04em` |
| `--tracking-wider` | Wider letter spacing | `0.08em` |

## Radius, Shadows, Motion, And Layers

| Token | Controls | Platform default |
| --- | --- | --- |
| `--radius-xs` | Extra-small radius | `2px` |
| `--radius-sm` | Small radius | `4px` |
| `--radius-md` | Medium radius | `8px` |
| `--radius-lg` | Large radius | `16px` |
| `--radius-xl` | XL radius | `24px` |
| `--radius-full` | Fully rounded radius | `9999px` |
| `--shadow-sm` | Small shadow | `0 1px 3px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.06)` |
| `--shadow-md` | Medium shadow | `0 4px 6px rgba(0, 0, 0, 0.07), 0 2px 4px rgba(0, 0, 0, 0.06)` |
| `--shadow-lg` | Large shadow | `0 10px 25px rgba(0, 0, 0, 0.1), 0 4px 10px rgba(0, 0, 0, 0.04)` |
| `--shadow-xl` | XL shadow | `0 20px 40px rgba(0, 0, 0, 0.15), 0 8px 16px rgba(0, 0, 0, 0.08)` |
| `--z-dropdown` | Dropdown layer | `100` |
| `--z-sticky` | Sticky layer | `200` |
| `--z-fixed` | Fixed layer | `300` |
| `--z-modal` | Modal layer | `400` |
| `--z-overlay` | Overlay layer | `500` |
| `--z-toast` | Toast layer | `600` |
| `--sobe-duration` | Base interaction duration | `200ms` |
| `--transition-fast` | Fast transition | `var(--sobe-duration) ease` |
| `--transition-base` | Base transition | `300ms ease` |
| `--transition-slow` | Slow transition | `500ms ease` |

Dark shadow defaults:

| Token | Controls | Platform default |
| --- | --- | --- |
| `--shadow-sm` | Dark small shadow | `0 1px 3px rgba(0, 0, 0, 0.3)` |
| `--shadow-md` | Dark medium shadow | `0 4px 6px rgba(0, 0, 0, 0.3)` |
| `--shadow-lg` | Dark large shadow | `0 10px 25px rgba(0, 0, 0, 0.4)` |
| `--shadow-xl` | Dark XL shadow | `0 20px 40px rgba(0, 0, 0, 0.45), 0 8px 16px rgba(0, 0, 0, 0.25)` |

## What If A Client Needs A New Token?

New CSS custom properties defined in `resources/css/client-tokens.css` work
immediately in client CSS and client block stylesheets. They are normal CSS
variables, so client CSS can use `var(--client-token-name)` as soon as the token
is declared.

New tokens do not automatically appear in the editor `theme.json` palette or
font controls. The editor palette is built from a curated token-name mapping in
`resources/scripts/build-theme-json.js`. A token the platform has never heard of
will not reach the editor unless that mapping is extended.

For overriding existing tokens, such as changing `--c-primary`, no build-script
change is needed. The Phase 6 override mechanism reads
`resources/css/client-tokens.css` after `resources/css/tokens.css`, and the
override value is picked up automatically.

If a client needs a new token in the editor palette, add the token name to the
`paletteMapping` list that builds `editorPalette` in
`resources/scripts/build-theme-json.js` in the client fork. This is a
client-side change, not a platform change.

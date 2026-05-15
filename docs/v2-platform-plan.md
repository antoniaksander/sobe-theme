# v2 Platform Migration Plan

Status: Pass 2 checkpoint. Do not begin Pass 3 migration planning until this classification is reviewed.

Reference tags created before audit:

- `pre-enrichment` -> `05eb85b`
- `enrichment-attempt-1` -> `9886ece`

Branch: `feat/v2-platform-audit`

## Stop-and-Ask Rule

Stop immediately before proceeding if any of these occur:

- An infrastructure item does not cleanly fit `PLATFORM`, `PLATFORM-WITH-HOOKS`, `EXAMPLE`, or `SANDBOX`.
- A WooCommerce hook surface has multiple reasonable long-term API designs.
- A library classification depends on a block promotion decision that has not been made.
- Any decision would require guessing about product or architecture intent.

Do not stop for document formatting, table organization, prose vs. bullets, or other presentation choices.

## Pass 1: Infrastructure Inventory

Source of truth for this pass:

- `demo/sobe` branch for sandbox platform candidates.
- Current branch, based on `main` after `9886ece`, for already-promoted thin-enrichment state.
- No source files were moved, copied, modified, or deleted during this inventory.

### Design Tokens

| Item | Files | Depends on | Notes |
|---|---|---|---|
| Font family tokens | `resources/css/tokens.css`, `fonts/*.woff2`, `resources/fonts/**` | Bundled Satoshi and CabinetGrotesk font files; `setup-patterns.php` font preload/inline face output | Demo uses brand-ish font defaults (`Satoshi`, `CabinetGrotesk`). Current main uses neutral system font defaults but still carries top-level font files. |
| Semantic light color tokens | `resources/css/tokens.css` | Tailwind `@theme`, editor palette extraction, WC aliases | Demo values are Sobe-tinted neutrals plus red accent. Current main has neutral white/slate/blue defaults. |
| Semantic dark color tokens | `resources/css/tokens.css` | `.dark` class on `html`; Alpine app shell | Demo uses dark gray plus red accent. Current main has neutral slate/blue dark values. |
| Overlay tokens | `resources/css/tokens.css` | Hero backgrounds, search overlay, filter drawer, image/dark-surface treatments | Demo includes fixed light overlay levels that are intentionally not inverted in dark mode. |
| Primary/accent/button tokens | `resources/css/tokens.css`, `resources/css/app.css` | Button component classes, WC buttons, editor palette | Demo separates semantic `--c-primary`/`--c-accent` from fixed `--c-btn-navy`/`--c-btn-cream`. Current main lacks the fixed button palette. |
| WooCommerce alias tokens | `resources/css/tokens.css`, `resources/css/woocommerce.css` | WC grid, PDP, forms, notices, cards, ratings, sale badges | Demo has a broad WC token layer. Current main has a smaller WC token subset for base styling. |
| Product category grid tokens | `resources/css/tokens.css`, `resources/blocks/product-categories-grid/style.scss` | Product categories grid block | Demo defines `--sobe-cat-grid-*` tokens for grid spacing, radius, sizing, zoom, and mobile slider aspect. |
| Layout width tokens | `resources/css/tokens.css`, `resources/css/app.css`, `resources/scripts/build-theme-json.js` | WP layout CSS variables, Tailwind max-width utilities, theme.json injection | Demo sets content/wide/standard to `90rem`; current main sets content to `72rem` and wide/standard to `90rem`. |
| Fluid spacing scale | `resources/css/tokens.css`, `resources/css/app.css` | Tailwind spacing aliases, sections, components, WC UI | Demo and current main both include `--space-xs` through `--space-3xl`. |
| Fluid text scale | `resources/css/tokens.css`, `resources/css/app.css`, `resources/scripts/build-theme-json.js` | Tailwind font-size aliases, editor font sizes | Demo has both `--text-*` tokens and separate `@theme --font-size-*` values; current main keeps the same token concept with neutral values. |
| Font weights and tracking | `resources/css/tokens.css`, `resources/css/app.css` | Headings, buttons, labels, WC filter UI | Demo defines normal/medium/semibold/bold plus tight/normal/wide/wider tracking. |
| Radius scale | `resources/css/tokens.css`, `resources/css/app.css`, block styles | Buttons, cards, forms, overlays, WC UI | Demo uses `2px` to `24px` token scale; `@theme` also defines Tailwind radii. |
| Shadow scale | `resources/css/tokens.css`, `resources/css/app.css` | Cards, overlays, dropdowns, dark mode | Demo defines light and dark shadow variants. Current main has neutral shadow variants. |
| Z-index scale | `resources/css/tokens.css`, app/overlay CSS | Dropdown, sticky, fixed, modal, overlay, toast layers | Demo and current main both define `--z-dropdown` through `--z-toast`. |
| Transition tokens | `resources/css/tokens.css`, app/blocks/WC CSS | Global UI transitions, reduced motion | Demo defines `--sobe-duration`, fast/base/slow transition aliases, and reduced-motion override. |
| UI surface variant tokens | `resources/css/tokens.css`, `resources/css/app.css` | Search overlay, filter chips, cards | Demo adds `--c-surface-raised`, `--c-surface-hover`, `--c-primary-subtle`, with dark variants. Current main does not include these. |
| Container query breakpoints | `resources/css/tokens.css`, `resources/css/woocommerce.css` | WC product card compact mode | Demo and current main define `--cq-product-card-compact`; demo WC CSS hardcodes `180px` in the actual `@container` rule. |
| Reduced motion primitive | `resources/css/tokens.css`, `resources/css/app.css`, `resources/js/app.js`, `resources/js/animations.js` | CSS transition timing, GSAP/Lenis guards | Demo combines token duration reduction with JS `prefers-reduced-motion` checks. |
| Selection tokens | `resources/css/tokens.css`, `resources/css/app.css` | `::selection` base styles | Demo selection foreground references `--color-background` from Tailwind `@theme`; current main uses primary/accent-derived neutral values. |

### CSS Architecture

| Item | Files | Depends on | Notes |
|---|---|---|---|
| Tailwind v4 import pipeline | `resources/css/app.css`, `resources/css/editor.css`, `vite.config.js` | `tailwindcss`, `@tailwindcss/vite` | Demo uses CSS-first Tailwind v4 with `@import 'tailwindcss'` and no separate Tailwind config file. |
| Token import | `resources/css/app.css`, `resources/css/editor.css`, `resources/css/woocommerce.css` | `resources/css/tokens.css` | Tokens are imported into frontend, editor, and WC bundles. |
| Tailwind content scanning | `resources/css/app.css` | `@source "../../app/**/*.php"`, `@source "../**/*.blade.php"`, `@source "../**/*.js"` | Demo scans PHP, Blade, and JS for utility usage. |
| Class-based dark variant | `resources/css/app.css`, `resources/css/tokens.css`, `resources/js/app.js` | Alpine `dark` state on `html` | Demo defines `@custom-variant dark (&:where(.dark, .dark *))`. Current main uses the same strategy in thinner form. |
| WordPress layout variable sync | `resources/css/app.css`, `resources/css/tokens.css` | `--layout-content`, `--layout-wide` | Demo overrides `--wp--style--global--content-size` and `--wp--style--global--wide-size` after WP head output. |
| Front page constrained layout rule | `resources/css/app.css`, `resources/views/front-page.blade.php` | Gutenberg constrained layout classes | Demo gives non-full front-page blocks max-width and padding while letting alignfull blocks span. |
| Tailwind `@theme` token bridge | `resources/css/app.css`, `resources/scripts/build-theme-json.js` | CSS custom properties and `wordpressThemeJson` plugin | Demo maps design tokens to Tailwind utility namespaces and editor/theme.json settings. |
| Base layer | `resources/css/app.css` | Tokens, Tailwind layers | Demo defines html/body typography, heading scale, focus-visible, selection, body flex layout. |
| Components layer | `resources/css/app.css` | Tokens, layout sections, headers, buttons, breadcrumbs, comments | Demo contains site header, WebGL canvas, x-cloak, breadcrumbs, comment form, nav dropdowns, and button system. |
| Utilities layer | `resources/css/app.css`, `resources/css/editor.css` | Theme.json palette slugs | Demo maps Gutenberg generated color classes back to live CSS variables. |
| Animation CSS hooks | `resources/css/app.css`, `resources/js/animations.js` | `data-animate`, GSAP | Demo hides unanimated elements until JS marks `data-animated`, with reduced-motion fallback. |
| Search overlay CSS | `resources/css/app.css`, `resources/views/partials/search-overlay.blade.php`, `resources/js/app.js` | Alpine focus plugin and REST search params | Demo styles modal, result rows, active states, and view-all link. |
| Search results page CSS | `resources/css/app.css`, `resources/views/search.blade.php`, search result partials | WP search template | Demo includes responsive cards and pagination for search results. |
| Page hero CSS | `resources/css/app.css`, `resources/views/partials/page-hero.blade.php` | Page meta `_sobe_page_hero` | Demo has generic page hero background/overlay/title styles. |
| Blog listing CSS | `resources/css/app.css`, `resources/views/index.blade.php`, post partials | Post meta `_sobe_post_cta`, featured images | Demo includes post row layout, media hover, excerpt, CTA styles. |
| WooCommerce CSS bundle | `resources/css/woocommerce.css` | WooCommerce pages, Swiper CSS, noUiSlider CSS, tokens | Demo WC bundle includes full catalog, product card, filter drawer, PDP grid/gallery, variation swatches, notices, cart/checkout/account support. Current main has a much thinner WC base stylesheet. |
| Block CSS bundle | `resources/css/blocks.css` | Historical block styles | Demo has a blocks bundle; current main does not. Need inspect during implementation if still referenced or stale. |
| Editor CSS bundle | `resources/css/editor.css`, `app/assets.php` | Vite editor asset injection | Demo mirrors token font/color mapping in block editor. |

### JS Application Shell

| Item | Files | Depends on | Notes |
|---|---|---|---|
| Alpine app root | `resources/js/app.js`, `resources/views/layouts/app.blade.php` | `alpinejs`, `x-data="app"` on `html` | Demo app owns nav, cart drawer, dark mode, cart announcements, focus restoration. Current main app only owns nav and dark mode. |
| Alpine Focus plugin | `resources/js/app.js`, overlays/nav templates | `@alpinejs/focus` | Demo uses `x-trap` / `x-trap.inert` for search, nav, and cart drawer accessibility. Current main does not depend on focus plugin. |
| Dark mode persistence | `resources/js/app.js`, `resources/views/components/dark-mode-toggle.blade.php`, `resources/css/tokens.css` | `localStorage`, `prefers-color-scheme`, `.dark` class | Demo only initializes dark mode if a toggle exists; current main sets initial `.dark` before Alpine starts. |
| Mobile navigation state | `resources/js/app.js`, header section templates | Alpine `navOpen` | Demo header variants consume a shared `navOpen` state. |
| Side-cart state and events | `resources/js/app.js`, `resources/views/components/side-cart.blade.php`, `resources/views/partials/side-cart-content.blade.php`, `app/woocommerce-sidecart.php` | WooCommerce fragments, Store API, Alpine, jQuery | Demo handles `open-cart`, `sobe:cart:item-added`, `cart-updated`, focus restoration, Lenis stop/start, and announcements. |
| Store API add-to-cart bridge | `resources/js/app.js`, `app/woocommerce-sidecart.php`, WC Store API | Woo Store API nonce, jQuery WC fragments | Demo intercepts single product simple/selected variable add-to-cart forms and emits cart events. |
| Toast manager | `resources/js/app.js`, `resources/views/components/toast-container.blade.php`, `app/Helpers/notice-helpers.php` | Alpine store, WC notices | Demo turns WC notices into toast data when side cart is disabled. |
| Search overlay Alpine component | `resources/js/app.js`, `app/setup-search.php`, `resources/views/partials/search-overlay.blade.php` | REST endpoint, Alpine Focus | Demo implements debounced query, active result keyboard navigation, close/open methods. |
| Lenis smooth scrolling | `resources/js/app.js` | `lenis`, GSAP ticker, desktop pointer/media checks | Demo creates `window.lenis` only when reduced motion is not requested and viewport/pointer checks pass. |
| GSAP animation bus | `resources/js/animations.js`, `resources/js/app.js`, block/WC markup `data-animate` | `gsap`, `ScrollTrigger` | Demo exposes `window.gsap`, `window.ScrollTrigger`, and `window.initAnimationBus` for cross-entry use after AJAX replacements. |
| Sticky header animation | `resources/js/animations.js`, header templates | GSAP ScrollTrigger | Demo hides/reveals `.site-header` on scroll and exposes `window.showSiteHeader`. |
| Catalog filter frontend | `resources/blocks/catalog-filters/view.js`, `resources/js/filter-store.js`, `resources/js/filter-utils.js`, `app/WooCommerce/FilterHandler.php` | `nouislider`, AJAX params, GSAP globals | Demo supports filter accordions, price range, chips, mobile drawer, AJAX product grid replacement, URL building, and animation refresh. |
| Shared filter store | `resources/js/filter-store.js`, `resources/blocks/catalog-filters/view.js`, `resources/js/shop-load-more.js` | Vite shared chunks | Demo creates singleton store for current filter state/action/nonce. |
| Filter URL utilities | `resources/js/filter-utils.js`, tests | Browser `URL` API | Demo pure functions build canonical filter URLs and detect active filters. |
| Shop load-more | `resources/js/shop-load-more.js`, `app/woocommerce-catalog.php`, pagination partial | IntersectionObserver, filter store | Demo supports infinite/load-more pagination, including active-filter mode and URL history option. |
| Product gallery JS | `resources/js/product-gallery.js`, `resources/views/woocommerce/content-single-product.blade.php` | Swiper, jQuery WC variation events, optional global PhotoSwipe | Demo owns quantity +/- controls, Swiper gallery/thumb sync, variation slide injection, static price updates, optional PhotoSwipe bridge. |
| Editor JS | `resources/js/editor.js`, `app/assets.php` | Vite editor entry, WP editor globals | Demo registers editor-side assets; detailed behavior not central in Pass 1 beyond asset entry. |
| Block view scripts | `resources/blocks/*/view.js` | Per-block frontend behavior | Demo uses view scripts for hero WebGL, FAQ accordion, product carousel Swiper, catalog filters, brand interactions, category grid Swiper, reviews slider. |

### Libraries

| Item | Files | Depends on | Notes |
|---|---|---|---|
| `alpinejs` | `package.json`, `resources/js/app.js`, Blade `x-*` markup | App shell, dark mode, nav, cart, search | Public in current main and demo. |
| `@alpinejs/focus` | `package.json`, `resources/js/app.js` | `x-trap` overlays/drawers | Demo-only dependency currently. Needed by search/nav/cart overlay accessibility if promoted. |
| `swiper` | `package.json`, `resources/blocks/product-carousel/view.js`, `resources/js/product-gallery.js`, `resources/blocks/product-categories-grid/view.js`, WC CSS | Product carousel, PDP gallery, product categories grid | Public in current main because product-carousel uses it. |
| `gsap` | `package.json`, `resources/js/app.js`, `resources/js/animations.js`, catalog filters | Animation bus, sticky header, Lenis ticker, AJAX refresh | Demo-only currently. |
| `lenis` | `package.json`, `resources/js/app.js` | Smooth scroll and cart drawer scroll locking | Demo-only currently. |
| `nouislider` | `package.json`, `resources/blocks/catalog-filters/view.js`, `resources/css/woocommerce.css` | Catalog price range filter | Demo-only currently. |
| React build support | `package.json`, `vite.config.js`, block `*.jsx` files | `@vitejs/plugin-react`, WP block editor JSX | Demo and current main use React tooling for editor blocks. |
| WordPress scripts | `package.json`, block tests/editor code | `@wordpress/scripts` | Dev dependency for WordPress block tooling. |
| Babel/Jest toolchain | `package.json`, `babel.config.json`, `jest.config.cjs`, tests | JS unit tests and JSX transform | Demo and current main use the same pattern. |
| Tailwind/Vite/Sage tooling | `package.json`, `vite.config.js`, CSS entries | Vite, Tailwind v4, Roots/Sage plugins, Laravel Vite plugin | Demo and current main share the same core tooling. |
| Acorn/Sage WooCommerce PHP deps | `composer.json`, `functions.php` | Roots Acorn, `generoi/sage-woocommerce` | Demo and current main both require PHP 8.4, Acorn 6, sage-woocommerce. |
| PHPStan/Pint/Woo stubs | `composer.json`, `phpstan.neon` | Static analysis | Demo and current main share analysis tooling. |
| Optional plugin globals | `resources/js/product-gallery.js`, `resources/views/components/breadcrumbs.blade.php`, `resources/views/components/wishlist-icon.blade.php`, `app/setup-search.php` | PhotoSwipe, Yoast, Rank Math, YITH Wishlist, Relevanssi | Demo detects plugins/globals but not all are package dependencies. |

### Customizer Settings

| Item | Files | Depends on | Notes |
|---|---|---|---|
| Header options section | `app/setup-customizer.php` | `config('theme.prefix')` | Registers `{prefix}_header_options`. Demo only; current main has no Customizer settings file. |
| Header layout setting | `app/setup-customizer.php`, `resources/views/layouts/app.blade.php`, `app/setup-demo-layout.php`, layout patterns | Header variants `header-1`, `header-2`, `header-3` | Drives `sobe_render_layout_pattern('header', ...)`. Current main includes pattern helper in `app/setup.php` rather than separate Customizer controls. |
| Dark toggle setting | `app/setup-customizer.php`, header sections, dark toggle component, shortcode | Alpine dark mode | Controls whether header renders toggle and shortcode returns markup. |
| Side-cart enable setting | `app/setup-customizer.php`, header sections, `app/woocommerce-sidecart.php`, notice helpers | WooCommerce | Controls header cart button and runtime side-cart behavior. |
| Header wishlist setting | `app/setup-customizer.php`, `resources/views/components/wishlist-icon.blade.php` | YITH Wishlist plugin | Shows header wishlist icon only when setting true and YITH class exists. |
| Light logo setting | `app/setup-customizer.php`, `app/View/Composers/App.php`, header sections, checkout header | WP media library | Stores attachment ID in `{prefix}_logo`. |
| Dark logo setting | `app/setup-customizer.php`, `app/View/Composers/App.php`, header sections, checkout header | WP media library, dark mode | Stores attachment ID in `{prefix}_dark_logo`. |
| Footer options section | `app/setup-customizer.php`, `resources/views/sections/footer.blade.php` | Layout pattern helper | Registers `{prefix}_footer_options`. |
| Footer layout setting | `app/setup-customizer.php`, footer section/block | Footer variants `layout-2`, `none` | Drives `sobe_render_layout_pattern('footer', ...)`. |
| Product card hover setting | `app/setup-customizer.php`, `resources/views/woocommerce/content-product.blade.php` | WC product gallery image IDs | Selects `zoom` or `swap` hover behavior. |
| Product catalog mobile columns | `app/setup-customizer.php`, `app/woocommerce-catalog.php`, `resources/css/woocommerce.css` | Body classes | Controls mobile product grid columns. |
| Product catalog tablet columns | Same as above | Body classes | Controls tablet product grid columns. |
| Product catalog desktop columns | Same as above plus `loop_shop_columns` | Body classes and WC loop props | Controls desktop grid columns and WC loop columns. |
| Products per page | `app/setup-customizer.php`, `app/woocommerce-catalog.php`, `app/WooCommerce/FilterHandler.php` | WC query filters/AJAX | Controls catalog and AJAX filter page size. |
| Shop pagination mode | `app/setup-customizer.php`, `app/woocommerce-catalog.php`, pagination partial, `shop-load-more.js` | AJAX load-more | Selects classic pagination or load-more sentinel. |
| Pagination history setting | `app/setup-customizer.php`, `app/woocommerce-catalog.php`, `shop-load-more.js` | Browser history | Controls URL updates in load-more mode. |
| Shop sidebar setting | `app/setup-customizer.php`, `resources/views/woocommerce/archive-product.blade.php` | Sidebar registration and catalog filters | Toggles sidebar/filter layout on shop archives. |

### Layout Pattern System

| Item | Files | Depends on | Notes |
|---|---|---|---|
| Layout pattern registration | `app/setup-patterns.php`, `resources/patterns/header-layout-*.php`, `resources/patterns/footer-layout-2.php` | WP block pattern APIs | Registers hidden header/footer layout patterns under `{prefix}/header-layout-*` and `{prefix}/footer-layout-2`. |
| Inserter-visible pattern category | `app/setup-patterns.php`, `resources/patterns/homepage-showcase.php` | Demo blocks | Demo registers `sobe/homepage-showcase` as visible pattern. Current main does not include this pattern. |
| Layout pattern category | `app/setup-patterns.php` | WP pattern registry | Hidden layout category `sobe-layout`; also block category with same slug. |
| Pattern render helper | `app/setup-demo-layout.php` in demo; `app/setup.php` in current main | `do_blocks`, `config('theme.prefix')` | `sobe_render_layout_pattern($type, $variant)` emits a `site-header` or `site-footer` block with variant attr. Current main already has this helper. |
| Header layout consumption | `resources/views/layouts/app.blade.php`, `resources/views/sections/header-*.blade.php` | Customizer header layout setting | Demo app renders selected header via helper. Current main also renders helper but with fewer surrounding app-shell features. |
| Footer layout consumption | `resources/views/sections/footer.blade.php`, `resources/views/blocks/site-footer.blade.php` | Customizer footer layout setting | Demo footer section delegates to layout pattern helper. |
| Layout example blocks | `resources/blocks/site-header`, `resources/blocks/site-footer`, `resources/views/blocks/site-header.blade.php`, `resources/views/blocks/site-footer.blade.php` | Section partials | These blocks are non-inserter, programmatic examples. Current main already includes thinner copies. |
| Header section variants | `resources/views/sections/header-1.blade.php`, `header-2.blade.php`, `header-3.blade.php`, `header.blade.php` | App composer logos, nav menu, dark toggle, search, wishlist, side cart | Demo has three named variants plus older `header.blade.php`. Current main has variants from prior enrichment. |
| Footer section variants | `resources/views/sections/footer-layout-2.blade.php`, `footer.blade.php` | Footer sidebar, site info | Demo has one concrete footer layout plus router section. |
| Checkout header variant | `resources/views/sections/checkout-header.blade.php`, `resources/views/layouts/app.blade.php` | WooCommerce checkout, App composer logo data | Demo swaps normal header for checkout-specific header on checkout pages. |

### Block Registration Architecture

| Item | Files | Depends on | Notes |
|---|---|---|---|
| Manifest-driven registration | `app/blocks.php`, `resources/blocks/blocks-manifest.json` | JSON manifest, Roots assets, Blade views | Demo and current main register blocks by manifest slug. |
| Dynamic render callbacks | `app/blocks.php`, `resources/views/blocks/*.blade.php` | Roots `view()` | All registered blocks render server-side through Blade. |
| Editor script registration | `app/blocks.php`, block `index.jsx` files | WP block editor globals | Registers one editor script per block slug. |
| Block style registration | `app/blocks.php`, block `style.scss`, `editor.scss` | Vite asset resolution | Conditionally registers frontend and editor styles when files exist. |
| View script registration | `app/blocks.php`, block `view.js` | Vite asset resolution | Conditionally registers frontend view scripts. |
| Module script tags | `app/blocks.php` | Handles beginning with `{prefix}-` | Adds `type="module"` to theme block scripts. |
| Allowed block types | `app/blocks.php`, `resources/scripts/check-patterns.js` | Core allowlist plus registered `{prefix}/*` blocks | Demo has inline core allowlist; current main also has `resources/config/core-allowed-blocks.json`. |
| Block categories | `app/blocks.php`, `app/setup-patterns.php` | `config('theme.prefix')` | Demo has generic, content, layout, WooCommerce categories. |
| Block scaffold script | `resources/scripts/make-block.js` | Node FS, manifest | Demo scaffold supports only `sobe-general` and `sobe-content`; current main likely broadened from prior fixes. |
| Block entry discovery | `resources/scripts/blocks-entries.js`, `vite.config.js` | Manifest and file existence checks | Adds index/style/editor/view entries to Vite input. |
| Pattern allowlist check | `resources/scripts/check-patterns.js`, `resources/patterns/*.php` | Manifest and hardcoded core/WC allowlists | Ensures patterns only use allowed blocks. Current main has related checker improvements. |
| Theme JSON build | `resources/scripts/build-theme-json.js`, `vite.config.js`, `theme.json` | Built `public/build/assets/theme.json`, tokens.css | Injects editor palette, font sizes, font families, content/wide sizes after Vite build. |

### Blade Component System

| Item | Files | Depends on | Notes |
|---|---|---|---|
| Alert component | `resources/views/components/alert.blade.php` | Tokens/Tailwind | Used by generic templates such as empty index results. Demo-only currently. |
| Badge component | `resources/views/components/badge.blade.php` | Tokens/Tailwind | Generic UI primitive. Demo-only currently. |
| Breadcrumbs component | `resources/views/components/breadcrumbs.blade.php`, `resources/css/app.css` | Yoast/RankMath detection, WP conditional tags | Provides SEO-plugin wrapper with fallback breadcrumb trail. Demo-only currently. |
| Button component | `resources/views/components/button.blade.php`, `resources/css/app.css` | Button token classes | Used by hero, product-carousel, product-feature. Demo-only currently; current main promoted blocks may need equivalent styling/markup. |
| Card component | `resources/views/components/card.blade.php` | Tokens/Tailwind | Generic UI primitive. Demo-only currently. |
| Dark mode toggle component | `resources/views/components/dark-mode-toggle.blade.php`, `resources/js/app.js` | Alpine app dark state | Current main already includes a copy. |
| Section component | `resources/views/components/section.blade.php`, page/index/single templates | Layout width and padding tokens | Provides width/padding wrapper for content templates. Demo-only currently. |
| Side-cart component | `resources/views/components/side-cart.blade.php`, `resources/views/partials/side-cart-content.blade.php`, `resources/js/app.js` | WooCommerce, Alpine, Store API | Drawer shell and refreshed content slot. Demo-only currently. |
| Toast container component | `resources/views/components/toast-container.blade.php`, `resources/js/app.js` | Alpine `toastManager` store | Displays WC/notices as toasts. Demo-only currently. |
| Wishlist icon component | `resources/views/components/wishlist-icon.blade.php`, `app/setup-customizer.php` | YITH Wishlist plugin | Header icon gated by Customizer and plugin class. Demo-only currently. |

### Helper Functions

| Item | Files | Depends on | Notes |
|---|---|---|---|
| Empty generic helpers namespace | `app/helpers.php` | Composer autoload/files inclusion | Demo has placeholder only. Current main likely same. |
| Layout pattern renderer | `app/setup-demo-layout.php` in demo; `app/setup.php` in current main | `do_blocks`, block naming convention | Emits programmatic `sobe/site-header` or `sobe/site-footer`. |
| Side-cart enabled helper | `app/Helpers/notice-helpers.php` | Customizer side-cart setting | Returns side-cart toggle state. |
| WC notices-to-toast helper | `app/Helpers/notice-helpers.php` | WooCommerce notice API | Converts WC notices to normalized toast arrays and clears notices. |
| Empty notices wrapper helper | `app/Helpers/notice-helpers.php` | WooCommerce fragments | Returns empty `.woocommerce-notices-wrapper` fragment. |
| Swatch value helper | `app/woocommerce-filters.php` | Term meta, YITH swatches meta, `sobe_swatch_value` filter | Resolves color/image/text swatch values for catalog filters. |
| Filtered term counts helper | `app/woocommerce-filters.php`, `app/WooCommerce/FilterHandler.php` | WP_Query, term APIs, direct DB query, cache | Computes interdependent product counts for categories, product_brand, and WC attributes. |
| App view composer helpers | `app/View/Composers/App.php` | Customizer logo settings | Provides site name, light logo, dark logo, current logo to views. |
| Post view composer helpers | `app/View/Composers/Post.php` | WP conditionals | Provides archive/search/404/page titles and pagination. Demo-only currently. |
| Comments view composer helpers | `app/View/Composers/Comments.php` | WP comments APIs | Provides comments title, response markup, previous/next links, closed state. Demo-only currently. |
| Product block composers | `app/View/Composers/ProductFeature.php`, `ProductCategoriesGrid.php`, `CatalogFilters.php` | WooCommerce products/taxonomies | Resolve runtime data for dynamic WC blocks. |

### WooCommerce Integration

| Item | Files | Depends on | Notes |
|---|---|---|---|
| Base WooCommerce support | `app/woocommerce.php` | WooCommerce plugin | Adds Woo support, lightbox, slider, wrapper removal/replacement, WC CSS enqueue. Current main has a thinner version. |
| WC frontend script policy | `app/woocommerce.php` | `WC_Frontend_Scripts`, `wc-cart-fragments` | Demo loads WC scripts on PDP/cart/checkout/account and cart fragments elsewhere; current main only loads scripts for product/cart/checkout/account. |
| Gallery aspect ratio config | `config/theme.php`, `app/woocommerce.php`, `resources/css/woocommerce.css` | Inline CSS var | Demo sets `--pdp-gallery-aspect-ratio` from config. Current main does not include this inline var. |
| Catalog column filters | `app/woocommerce-catalog.php`, `app/setup-customizer.php`, `resources/css/woocommerce.css` | Customizer settings, body classes | Controls loop columns and CSS grid columns across breakpoints. |
| Catalog products per page | `app/woocommerce-catalog.php`, `FilterHandler.php`, Customizer | WC query filters | Controls normal and AJAX catalog query size. |
| Catalog body classes | `app/woocommerce-catalog.php`, `resources/css/woocommerce.css` | Customizer settings | Adds `{prefix}-catalog-*-columns-*` classes to shop/product taxonomy pages. |
| Shop pagination replacement | `app/woocommerce-catalog.php`, `resources/views/woocommerce/loop/pagination.blade.php`, `resources/js/shop-load-more.js` | WC hooks, Customizer mode | Removes default pagination and renders classic or load-more platform pagination. |
| Load-more AJAX handler | `app/woocommerce-catalog.php`, `resources/js/shop-load-more.js` | WP AJAX, nonce, WC template parts | Returns product HTML, `has_more`, and next page. |
| Catalog filters block | `resources/blocks/catalog-filters`, `resources/views/blocks/catalog-filters.blade.php`, `CatalogFilters.php`, `FilterHandler.php`, `woocommerce-filters.php` | Woo product categories, attributes, product_brand, noUiSlider | Full filter UI and AJAX query layer. |
| Product card template override | `resources/views/woocommerce/content-product.blade.php`, `app/woocommerce-pdp.php`, `resources/css/woocommerce.css` | Woo hook zones, YITH optional shortcode, product_brand | Blade owns product card shell, image hover, wishlist wrapper, sale badge, title, rating/price zones. |
| Product archive template override | `resources/views/woocommerce/archive-product.blade.php` | Woo hooks, sidebar setting, sidebar-shop | Full shop layout shell with optional sidebar and mobile filter drawer. |
| Single product template override | `resources/views/woocommerce/single-product.blade.php`, `content-single-product.blade.php` | Woo hooks, Swiper, product gallery JS | Full PDP layout shell. |
| PDP hook policy | `app/woocommerce-pdp.php` | Woo hooks and tabs | Removes default title/gallery/excerpt/tabs/add-to-cart-card pieces, adds brand label and extra product tabs. |
| PDP Swiper gallery | `resources/views/woocommerce/content-single-product.blade.php`, `resources/js/product-gallery.js`, `resources/css/woocommerce.css` | Swiper, jQuery WC variation events, optional PhotoSwipe globals | Replaces native WC gallery with Swiper main/thumb gallery and optional lightbox bridge. |
| PDP accordions/tabs | `resources/views/woocommerce/content-single-product.blade.php`, `app/woocommerce-pdp.php`, `resources/css/woocommerce.css` | `woocommerce_product_tabs` filter | Renders tabs as accordions and adds Shipping Information/Product Details tabs. |
| Related/upsell overrides | `resources/views/woocommerce/single-product/related.blade.php`, `up-sells.blade.php`, WC CSS | Woo related/upsell data | Provides section wrapper and product loop rendering. |
| Notice overrides | `resources/views/woocommerce/notices/*.blade.php`, `app/woocommerce-sidecart.php` | Woo notice templates/fragments | Simplified notice markup plus side-cart/toast suppression policy. |
| Side-cart fragments | `app/woocommerce-sidecart.php`, `resources/views/components/side-cart.blade.php`, `partials.side-cart-content` | Woo fragments, Store API nonce, Alpine | Refreshes cart content and cart count fragments. |
| Side-cart redirect/open policy | `app/woocommerce-sidecart.php`, `resources/js/app.js` | `sobe_open_cart` query param, WC add-to-cart redirect | Opens cart after non-AJAX PDP add-to-cart. |
| Store API cart item mutation | `resources/js/app.js`, `partials.side-cart-content` | WC Store API nonce | Updates/removes cart items and refreshes WC fragments. |
| Checkout header | `resources/views/sections/checkout-header.blade.php`, layout app | Woo checkout conditional | Replaces normal site header on checkout. |
| Account/cart/checkout styling | `resources/css/woocommerce.css`, `app/woocommerce.php` | Woo page conditionals | Demo stylesheet covers broad WC surfaces; exact coverage needs implementation-phase CSS review. |
| Variation swatches plugin styling | `resources/css/woocommerce.css` | Woo Variation Swatches plugin classes | Demo includes plugin-specific swatch overrides. |
| YITH wishlist UI | `components/wishlist-icon.blade.php`, `content-product.blade.php`, `setup-patterns.php`, `setup-customizer.php` | YITH Wishlist plugin/class/shortcodes | Header icon and product-card wrapper are plugin-dependent; fallback shortcode prevents raw text leakage. |

### SEO

| Item | Files | Depends on | Notes |
|---|---|---|---|
| Baseline SEO meta | `resources/views/layouts/app.blade.php` | WP title/excerpt/permalink/site icon APIs | Demo emits canonical, description, OpenGraph, Twitter, and article meta when no SEO plugin is active. Current main does not include this baseline SEO block. |
| SEO plugin bypass | `resources/views/layouts/app.blade.php` | Yoast, Rank Math, AIOSEO, SEOPress constants/functions; `sobe_disable_baseline_seo` filter | Demo skips baseline meta when a supported SEO plugin is active or filter disables it. |
| Organization schema | `resources/views/layouts/app.blade.php` | Front page, site icon | Demo emits Organization JSON-LD on front page when baseline SEO is active. |
| Breadcrumb component | `resources/views/components/breadcrumbs.blade.php`, `resources/css/app.css` | Yoast, Rank Math, WP fallbacks | Uses SEO plugin breadcrumbs when available, otherwise builds simple fallback trail. |
| Search result semantic cards | `resources/views/search.blade.php`, search result partials | WP search loop, product data | Demo search templates provide content-type labels, excerpts, prices, dates. |
| Relevanssi taxonomy indexing hook | `app/setup-search.php` | Relevanssi plugin, product_brand taxonomy | Demo adds `product_brand` to indexed taxonomies when Relevanssi is present. |
| No sitemap hooks found | `app/**`, `resources/**` | N/A | Inventory found baseline meta/schema/breadcrumbs, but no custom sitemap hook implementation. |

### Block Inventory

| Item | Files | Depends on | Notes |
|---|---|---|---|
| `sobe/example` | `resources/blocks/example/*`, `resources/views/blocks/example.blade.php` | Block registration scaffold | Minimal dynamic infrastructure block. Present in demo and current main. |
| `sobe/hero` | `resources/blocks/hero/*`, `resources/views/blocks/hero.blade.php` | Button component, tokens, optional `view.js` WebGL, GSAP animation data attrs | Demo full hero supports heading, paragraph, CTA, CTA type, heading/paragraph colors, heading size, left/center/split/editorial alignment, height, background image, dark overlay, WebGL flag. Current main has stripped version from enrichment attempt. |
| Broken hero WebGL | `resources/blocks/hero/view.js`, `resources/views/layouts/app.blade.php`, `resources/css/app.css` | Global `#global-webgl` canvas, browser canvas APIs, reduced-motion media query | User decision: excluded later because broken in demo, not because WebGL is too advanced. Pass 1 only records it. |
| `sobe/faq` | `resources/blocks/faq/*`, `resources/views/blocks/faq.blade.php` | FAQ view script, block styles, animation bus data attr | Accordion FAQ with `faqs` array of question/answer. Present in demo and current main. |
| `sobe/product-carousel` | `resources/blocks/product-carousel/*`, `resources/views/blocks/product-carousel.blade.php` | WooCommerce, Swiper, Button component, product card template | Supports count, orderBy, categoryId, brandId, heading, paragraph, link text/url/type. Current main has promoted version but previous attempt stripped brand filtering. |
| `sobe/product-feature` | `resources/blocks/product-feature/*`, `resources/views/blocks/product-feature.blade.php`, `ProductFeature.php` | WooCommerce product lookup, product_brand taxonomy, Button component | Two-column product showcase with selected product, layout, image ratio, show/hide product data, custom brand text, editorial copy and CTA. |
| `sobe/catalog-filters` | `resources/blocks/catalog-filters/*`, `resources/views/blocks/catalog-filters.blade.php`, `CatalogFilters.php`, `FilterHandler.php`, `woocommerce-filters.php` | WooCommerce categories/attributes, product_brand, noUiSlider, AJAX | Full catalog filter UI with active chips, price type, categories, brands, attributes, price range, mobile drawer. |
| `sobe/brand-carousel` | `resources/blocks/brand-carousel/*`, `resources/views/blocks/brand-carousel.blade.php` | `product_brand` taxonomy or manual entries, CSS marquee, animation bus | Manual or taxonomy-driven brand/logo carousel. Name and auto mode are product_brand coupled. |
| `sobe/our-brands` | `resources/blocks/our-brands/*`, `resources/views/blocks/our-brands.blade.php` | `product_brand` taxonomy, optional term logos, Lenis smooth scroll | Alphabetical Woo brand directory pulled from product_brand. |
| `sobe/reviews-slider` | `resources/blocks/reviews-slider/*`, `resources/views/blocks/reviews-slider.blade.php` | WooCommerce reviews or manual entries, custom view script | Full testimonial/review slider with auto/products/manual modes, ratings, product links/images, autoplay. |
| `sobe/product-categories-grid` | `resources/blocks/product-categories-grid/*`, `resources/views/blocks/product-categories-grid.blade.php`, `ProductCategoriesGrid.php` | WooCommerce product categories, Swiper for mobile/nav | Showcase selected product categories with several layouts and hover effects. |
| `sobe/site-header` | `resources/blocks/site-header/*`, `resources/views/blocks/site-header.blade.php`, header section templates | Layout pattern router | Non-inserter programmatic header block with `variant`. Present in demo and current main. |
| `sobe/site-footer` | `resources/blocks/site-footer/*`, `resources/views/blocks/site-footer.blade.php`, footer section templates | Layout pattern router | Non-inserter programmatic footer block with `variant`. Present in demo and current main. |
| Missing section/testimonial/team/pricing blocks | `resources/blocks/` | N/A | These aspirational blocks do not exist in `demo/sobe`; if referenced later they are follow-up build work, not migration candidates from demo. |

### Patterns

| Item | Files | Depends on | Notes |
|---|---|---|---|
| Homepage showcase pattern | `resources/patterns/homepage-showcase.php`, `app/setup-patterns.php` | Hero, brand carousel, product feature and/or other demo blocks | Inserter-visible demo pattern. Not in current main. |
| Header layout 1 pattern | `resources/patterns/header-layout-1.php`, `site-header` block | Layout renderer | Hidden internal layout pattern. Present in demo and current main. |
| Header layout 2 pattern | `resources/patterns/header-layout-2.php`, `site-header` block | Layout renderer | Hidden internal layout pattern. Present in demo and current main. |
| Header layout 3 pattern | `resources/patterns/header-layout-3.php`, `site-header` block | Layout renderer | Hidden internal layout pattern. Present in demo and current main. |
| Footer layout 2 pattern | `resources/patterns/footer-layout-2.php`, `site-footer` block | Layout renderer | Hidden internal layout pattern. Present in demo and current main. |
| Pattern allowlist enforcement | `resources/scripts/check-patterns.js`, `package.json` | Manifest, core/WC allowlist | Demo validates pattern block names in CI. |

### Theme JSON Build

| Item | Files | Depends on | Notes |
|---|---|---|---|
| Built theme.json redirection | `app/assets.php`, `theme.json`, `public/build/assets/theme.json` | `theme_file_path` filter, Vite build | WP reads built theme.json from public build assets. |
| Vite WordPress theme JSON plugin | `vite.config.js` | `@roots/vite-plugin` | Generates build-time theme.json from Tailwind/CSS inputs. |
| Post-build injection plugin | `vite.config.js`, `resources/scripts/build-theme-json.js` | Node `execSync`, built theme.json path | Demo injects editor settings after bundle generation. |
| Palette extraction | `resources/scripts/build-theme-json.js`, `resources/css/tokens.css` | Regex against `:root` token blocks | Extracts hex/rgb colors for curated editor palette. |
| Font size injection | `resources/scripts/build-theme-json.js` | `--font-size-*` aliases | Injects XS through 7XL font sizes using CSS variable references. |
| Font family injection | `resources/scripts/build-theme-json.js` | System font stacks | Injects Sans/Serif/Mono families; does not mirror demo bundled brand fonts directly. |
| Layout injection | `resources/scripts/build-theme-json.js`, `resources/css/tokens.css` | `--layout-content`, `--layout-wide` | Injects `settings.layout.contentSize` and `wideSize`. |

### Translations / i18n

| Item | Files | Depends on | Notes |
|---|---|---|---|
| Textdomain config | `config/theme.php`, PHP/Blade translations | `config('theme.textdomain')` and hardcoded `'sobe'` in many files | Demo uses both configured textdomain and direct `'sobe'`; some block metadata/editor strings still use `'sage'`. |
| Translation npm scripts | `package.json` | WP CLI i18n commands, `resources/lang` | Demo and current main include `translate:pot`, `translate:update`, `translate:compile`, `translate:js`, `translate:mo`. |
| PHP/Blade translation usage | `app/**`, `resources/views/**` | WordPress i18n functions | Extensive `__`, `_x`, `_n`, `_nx`, `esc_html__` usage across app, WC, templates, components. |
| Block editor translation usage | `resources/blocks/**/*.jsx`, `block.json` | `wp.i18n`, block metadata textdomain | Mixed `sobe`/`sage` usage found in demo block files. |
| POT output target | `package.json` | `resources/lang/sobe.pot` | `resources/lang` directory is not present in the demo tree listing; scripts assume it exists or will be created. |

### Asset Pipeline

| Item | Files | Depends on | Notes |
|---|---|---|---|
| Vite entrypoints | `vite.config.js`, `resources/scripts/blocks-entries.js` | CSS/JS app/editor entries plus manifest block entries | Demo inputs include app CSS/JS, editor CSS/JS, Woo CSS, and all block assets. |
| Laravel Vite plugin | `vite.config.js`, Blade `@vite` directive | `laravel-vite-plugin`, Acorn/Sage integration | Demo uses `@vite(['resources/css/app.css', 'resources/js/app.js'])` in layout. |
| Roots WordPress plugin | `vite.config.js`, `app/assets.php` | `@roots/vite-plugin` | Provides WP/editor asset integration and theme.json build support. |
| React JSX support | `vite.config.js`, block `*.jsx` | `@vitejs/plugin-react`, esbuild automatic JSX | Demo blocks use JSX while relying on WP globals for packages. |
| Bundle size budget | `vite.config.js` | Vite plugin hook | Demo enforces app CSS <= 150kB and app JS <= 250kB during build. |
| Editor asset injection | `app/assets.php`, `resources/js/editor.js`, `resources/css/editor.css` | Vite facade, `editor.deps.json` | Demo enqueues media, editor CSS, editor script/deps in block editor. |
| Asset aliases | `vite.config.js` | `@scripts`, `@styles`, `@images` | Demo defines aliases for source imports. |
| Public build theme.json | `app/assets.php`, `resources/scripts/build-theme-json.js` | Vite build output | WP theme.json source redirected to build artifact. |
| Block module output | `app/blocks.php`, `vite.config.js` | Per-block Vite entries and script tag filter | Block scripts are treated as ES modules. |

### Service Providers

| Item | Files | Depends on | Notes |
|---|---|---|---|
| Acorn application boot | `functions.php` | Composer autoload, Roots Acorn | Demo and current main boot Acorn with `ThemeServiceProvider`. |
| Theme service provider | `app/Providers/ThemeServiceProvider.php` | `Roots\Acorn\Sage\SageServiceProvider` | Demo provider only calls parent register/boot; no custom services. |
| Composer autoload PSR-4 | `composer.json`, `app/**` | Composer | Maps `App\` to `app/`. |

### Setup Hooks

| Item | Files | Depends on | Notes |
|---|---|---|---|
| Theme support cleanup | `app/setup.php` | WP theme supports | Removes block templates and core block patterns. |
| Navigation menus | `app/setup.php`, header/footer sections | WP menus | Registers primary and footer navigation. |
| Core theme supports | `app/setup.php` | WordPress | Adds title tag, thumbnails, align-wide, responsive embeds, feeds, selective refresh. |
| HTML5 supports | `app/setup.php` | WordPress | Adds HTML5 support for caption, forms, lists, gallery, search, script, style. |
| Configured image sizes | `app/setup.php`, `config/theme.php` | `config('theme.image_sizes')` | Demo adds `{prefix}-hero` image size. |
| Excerpt filters | `app/filters.php`, `config/theme.php` | WP excerpt filters | Sets excerpt length and "Continued" link. |
| Page display meta | `app/setup-patterns.php`, page templates | WP REST meta | Registers `_sobe_page_hero` and `_sobe_hide_title`. |
| Post CTA meta | `app/setup-patterns.php`, post listing templates | WP REST meta | Registers `_sobe_post_cta`. |
| Product brand taxonomy | `app/setup-patterns.php`, WC blocks/templates/filters/search | WooCommerce product post type | Registers `product_brand` taxonomy. This is a major shared assumption in demo. |
| Font preload/inline face hooks | `app/setup-patterns.php`, top-level `fonts/*.woff2` | Filesystem checks, `wp_head` | Preloads and declares Satoshi/CabinetGrotesk if files exist. |
| Dark toggle shortcode | `app/setup-patterns.php`, dark toggle component, Customizer | `Roots\view` | Registers `{prefix}_dark_toggle` shortcode gated by Customizer setting. |
| Sidebar registration | `app/setup-patterns.php`, sidebar sections, footer layout, shop archive | WP widgets | Registers primary, footer, and shop sidebar. |
| YITH wishlist fallback shortcode | `app/setup-patterns.php` | YITH constant detection | Prevents raw wishlist shortcode output if plugin inactive. |
| Function include list | `functions.php` | `locate_template` | Demo loads helpers, setup, blocks, assets, filters, Woo/security/pattern/customizer/search files. Current main only loads the thin core set. |

### Security Baseline

| Item | Files | Depends on | Notes |
|---|---|---|---|
| Head cleanup | `app/security.php` | WP head actions | Removes generator, RSD, WLW manifest, shortlink tags. Present in demo and current main. |
| XML-RPC disabled | `app/security.php` | `xmlrpc_enabled`, init request guard | Demo disables XML-RPC and blocks `xmlrpc.php` URI with 403. |
| REST API access control | `app/security.php` | `rest_pre_dispatch`, login state, Woo Store API, theme search endpoint | Demo blocks unauthenticated REST except WC cart routes and `{prefix}/v1/search`. Current main security must be compared before promotion because endpoint allowlist expands with search/cart. |
| Store API public routes | `app/security.php`, `resources/js/app.js` | Woo Store API cart/add/items | Required for side-cart and PDP add-to-cart flow. |
| Search endpoint public route | `app/security.php`, `app/setup-search.php` | `{prefix}/v1/search` route | Required for public search overlay. |
| Nonce usage | `app/woocommerce-catalog.php`, `app/woocommerce-filters.php`, `app/woocommerce-sidecart.php`, `resources/js/app.js` | WP AJAX and WC Store API nonces | Demo uses separate load-more, filter, and Store API nonces. |
| Sanitization/escaping | `app/**/*.php`, `resources/views/**/*.blade.php` | WP sanitize/escape helpers | Demo uses sanitize callbacks, `esc_*`, `wp_kses_post`, `wc_kses_notice`; Pass 2/3 should still audit hook contracts for escaping boundaries. |

### Testing & Linting

| Item | Files | Depends on | Notes |
|---|---|---|---|
| Jest config | `jest.config.cjs`, `babel.config.json` | `babel-jest`, Node test env | Demo transforms CJS/JS/JSX and maps SCSS to mock. |
| Block metadata tests | `tests/blocks/meta.test.cjs` | Manifest and block.json files | Ensures name matches `sobe/{slug}`, apiVersion 3, supports.html false, category matches manifest. |
| Block save tests | `tests/blocks/save.test.cjs` | Block save.jsx files | Ensures dynamic blocks return null. |
| Filter store tests | `tests/shop/filter-store.test.cjs` | `resources/js/filter-store.js` | Unit tests singleton filter store behavior. Demo-only currently. |
| Filter URL utility tests | `tests/shop/filter-utils.test.cjs` | `resources/js/filter-utils.js` | Unit tests canonical filter URLs and active filter detection. Demo-only currently. |
| PHPStan config | `phpstan.neon`, `composer.json` | WordPress and WooCommerce stubs | Level 2 on `app`, with Acorn helper and Vite facade ignores. |
| Composer analyse script | `composer.json` | PHPStan | Runs `phpstan analyse --memory-limit=2G`. |
| CI workflow | `.github/workflows/ci.yml` | PHP 8.4, Node 22, composer, npm | Runs composer install, npm ci, environment parity check, build, PHPStan, Jest, pattern allowlist. |
| Pattern checker script | `resources/scripts/check-patterns.js`, `package.json` | Manifest, pattern files | Included in CI as `npm run check:patterns`. |
| Environment requirements | `.site-requirements.json`, CI | `jq`, PHP/Node versions | CI verifies PHP and Node parity. |
| Formatting/linting notes | `package.json`, `composer.json` | Laravel Pint present in require-dev | No explicit npm lint/prettier scripts found in demo package.json; Pint is installed but no composer script is listed for formatting. |

## Pass 1 Current-Main Delta Notes

Current `main` after `9886ece` already contains a thin subset of demo infrastructure:

- Neutral token system, dark overrides, layout width tokens, and reduced-motion duration token.
- Minimal Alpine app shell for dark mode and nav.
- Manifest registration for `example`, `hero`, `faq`, `product-carousel`, `site-header`, and `site-footer`.
- Layout pattern helper and hidden header/footer patterns.
- Basic WooCommerce support and base stylesheet.
- Swiper dependency for `sobe/product-carousel`.

Major demo-only areas not yet in current `main`:

- Customizer platform settings.
- Full JS app shell: Alpine Focus, side-cart, search, toast manager, Store API add-to-cart, Lenis, GSAP animation bus.
- Full WooCommerce catalog, filters, PDP, side-cart, wishlist, search integration, and template overrides.
- Blade component library beyond dark mode toggle.
- Full block set beyond the already-promoted subset.
- SEO baseline meta/schema and breadcrumbs.
- Product brand taxonomy and dependent brand/catalog features.
- Shop filter JS utilities and tests.

## Pass 1 Checkpoint

Pass 1 was reviewed and approved. Pass 2 decisions applied:

- `product_brand` taxonomy is `PLATFORM`, registered behind `apply_filters('sobe/register_product_brand', true)`.
- When demo and current main differ, demo wins unless current main has a clear bug fix, cleaner refactor, or more correct WordPress pattern.
- Demo brand values must be stripped during implementation: brand colors, agency-name copy, brand-specific defaults, hardcoded identifiers.
- Textdomain standardizes to `sobe` everywhere.
- `resources/lang/` remains unresolved and low priority for Pass 3.

## Pass 2: Boundary Classification

Bucket meanings:

- `PLATFORM`: moves to main as-is or near-as-is after neutralizing brand values.
- `PLATFORM-WITH-HOOKS`: moves to main, but only with explicit extension hooks.
- `EXAMPLE`: moves to main as a reference implementation; clients copy to their own namespace or override through documented extension points.
- `SANDBOX`: stays in `demo/sobe`.

### Classification Index

| Category | Items | Bucket | Reasoning |
|---|---|---:|---|
| Design Tokens | Font family tokens | `PLATFORM` | Token names and font-family slots are infrastructure. Demo's Satoshi/Cabinet values are brand defaults and must be reset to neutral system defaults. |
| Design Tokens | Semantic light color tokens, semantic dark color tokens, primary/accent/button tokens | `PLATFORM` | Color contract belongs in main; values must be neutralized from Sobe red/cream/navy. |
| Design Tokens | Overlay tokens, selection tokens, UI surface variant tokens | `PLATFORM` | Generic UI primitives used by hero, overlays, filters, and cards. |
| Design Tokens | WooCommerce alias tokens | `PLATFORM` | Main owns WC integration, so WC token aliases are part of the shared styling contract. |
| Design Tokens | Product category grid tokens | `PLATFORM` | The product-categories block is a generic WC block; its sizing/motion tokens move with it. |
| Design Tokens | Layout width tokens, fluid spacing scale, fluid text scale, font weights and tracking, radius scale, shadow scale, z-index scale, transition tokens, container query breakpoints, reduced motion primitive | `PLATFORM` | Core design-system primitives every client inherits and overrides by value, not by renaming. |
| CSS Architecture | Tailwind v4 import pipeline, token import, Tailwind content scanning, class-based dark variant, WordPress layout variable sync, Tailwind `@theme` token bridge, base layer, utilities layer, editor CSS bundle | `PLATFORM` | These are build/runtime CSS infrastructure. |
| CSS Architecture | Front page constrained layout rule, page hero CSS, blog listing CSS, comments CSS inside components layer | `EXAMPLE` | Useful default templates, but client presentation may replace them. They should move as working defaults, not as client-specific contract. |
| CSS Architecture | Components layer, search overlay CSS, search results page CSS | `PLATFORM-WITH-HOOKS` | Shared UI shells move, but clients need class/partial/hook surfaces for structural changes. |
| CSS Architecture | WooCommerce CSS bundle | `PLATFORM-WITH-HOOKS` | Main owns WC integration; clients must extend tokens/classes and hooks instead of replacing the full layer. |
| CSS Architecture | Block CSS bundle | `SANDBOX` | Demo `blocks.css` appears historical/stale and is not referenced by the current Vite input. Implementation should not promote it unless a later source check proves it is used. |
| JS Application Shell | Alpine app root, Alpine Focus plugin, dark mode persistence, mobile navigation state, toast manager, Lenis smooth scrolling, GSAP animation bus, sticky header animation, editor JS, generic block view script convention | `PLATFORM` | These are shared app-shell capabilities. Demo wins over current thin main, with dark-mode initialization reviewed during implementation. |
| JS Application Shell | Side-cart state and events, Store API add-to-cart bridge, search overlay Alpine component, catalog filter frontend, shared filter store, filter URL utilities, shop load-more, product gallery JS | `PLATFORM-WITH-HOOKS` | These are platform features with long-lived extension APIs. |
| Libraries | `alpinejs`, `@alpinejs/focus`, `swiper`, `gsap`, `lenis`, `nouislider` | `PLATFORM` | Public dependencies move when the promoted public features that import them move. Details in library audit. |
| Libraries | React build support, WordPress scripts, Babel/Jest toolchain, Tailwind/Vite/Sage tooling, Acorn/Sage WooCommerce PHP deps, PHPStan/Pint/Woo stubs | `PLATFORM` | Build/test/runtime infrastructure. |
| Libraries | Optional plugin globals | `PLATFORM-WITH-HOOKS` | Plugin integrations must be detected and hookable, not hard requirements. |
| Customizer Settings | Header/footer sections and layout settings, dark toggle, side-cart enable, logo settings, product card hover, catalog columns, products per page, pagination mode/history, shop sidebar | `PLATFORM` | Platform settings let clients configure inherited features without source edits. Defaults must be generic. |
| Customizer Settings | Header wishlist setting | `PLATFORM-WITH-HOOKS` | Wishlist UI must become plugin-agnostic while preserving YITH support as an adapter. |
| Layout Pattern System | Layout pattern registration, layout pattern category, pattern render helper, header/footer layout consumption | `PLATFORM` | The pattern router is shared infrastructure. |
| Layout Pattern System | Inserter-visible homepage showcase pattern | `SANDBOX` | Demo homepage composition is sandbox/demo content. |
| Layout Pattern System | Layout example blocks, header section variants, footer section variants | `EXAMPLE` | Move as pedagogical `sobe/*` examples. Clients copy into their own namespace for bespoke layout. |
| Layout Pattern System | Checkout header variant | `PLATFORM-WITH-HOOKS` | Checkout shell belongs to WC integration, but clients need hooks/partials for logo, return link, and trust messaging. |
| Block Registration Architecture | Manifest registration, render callbacks, editor/style/view script registration, module script tags, block categories, block entry discovery, pattern allowlist check, theme JSON build | `PLATFORM` | Core block infrastructure. |
| Block Registration Architecture | Allowed block types | `PLATFORM-WITH-HOOKS` | Keep core/WC/manifest allowlist, but expose filters for clients to add private blocks and plugin blocks. |
| Block Registration Architecture | Block scaffold script | `PLATFORM` | Keep scaffold infrastructure; Pass 3 should prefer current main if it has the cleaner namespace/category fixes. |
| Blade Component System | Alert, badge, button, card, dark-mode-toggle, section | `PLATFORM` | Generic UI primitives used by public blocks/templates. |
| Blade Component System | Breadcrumbs | `PLATFORM-WITH-HOOKS` | SEO plugin fallback is platform, but clients need filters for trail items and rendering. |
| Blade Component System | Side-cart, toast container, wishlist icon | `PLATFORM-WITH-HOOKS` | Commerce UI shells need stable hooks and plugin adapters. |
| Helper Functions | Empty generic helpers namespace, layout pattern renderer | `PLATFORM` | Shared helper namespace and layout router. |
| Helper Functions | Side-cart helpers, WC notices-to-toast helper, empty notices wrapper helper, swatch value helper, filtered term counts helper | `PLATFORM-WITH-HOOKS` | Shared WC behavior needs filters for notices, swatches, counts, and side-cart state. |
| Helper Functions | App view composer helpers | `PLATFORM` | Logo/site data is generic platform data. |
| Helper Functions | Post and comments composers | `EXAMPLE` | Useful default WordPress presentation, not hard platform contract. |
| Helper Functions | Product block composers | `PLATFORM-WITH-HOOKS` | Public WC blocks need hookable data resolution. |
| WooCommerce Integration | Base WooCommerce support, frontend script policy, gallery aspect ratio config, catalog products per page, catalog body classes, account/cart/checkout styling | `PLATFORM` | Main owns full working WC integration. |
| WooCommerce Integration | Catalog column filters, shop pagination replacement, load-more AJAX handler, catalog filters block, product card template override, product archive template override, single product template override, PDP hook policy, PDP Swiper gallery, PDP accordions/tabs, related/upsell overrides, notice overrides, side-cart fragments, side-cart redirect/open policy, Store API cart mutation, checkout header, variation swatches plugin styling, YITH wishlist UI | `PLATFORM-WITH-HOOKS` | These define the commerce extension surface and must be hook-first. |
| SEO | Baseline SEO meta, SEO plugin bypass, Organization schema, Relevanssi taxonomy indexing hook | `PLATFORM-WITH-HOOKS` | Platform should work without plugins and defer to plugins; clients need filters for SEO data and integration. |
| SEO | Breadcrumb component, search result semantic cards | `PLATFORM-WITH-HOOKS` | Default rendering moves with filters/partials for client-specific output. |
| SEO | No sitemap hooks found | `SANDBOX` | No infrastructure exists to move. If sitemap support is desired it is follow-up work. |
| Block Inventory | `sobe/example`, `sobe/faq`, `sobe/product-categories-grid` | `PLATFORM` | Generic infrastructure/content/WC blocks. |
| Block Inventory | `sobe/hero` | `EXAMPLE` | Move full demo hero minus broken WebGL as a strong reference block. Clients can use it directly or create a client hero namespace for bespoke work. |
| Block Inventory | Broken hero WebGL | `SANDBOX` | Explicitly excluded because broken in demo. Keep out until rebuilt deliberately. |
| Block Inventory | `sobe/product-carousel`, `sobe/product-feature`, `sobe/catalog-filters`, `sobe/brand-carousel`, `sobe/our-brands`, `sobe/reviews-slider` | `PLATFORM-WITH-HOOKS` | WC/data-backed public blocks need filters for query/data/rendering. Reviews-slider has a pending recommendation below. |
| Block Inventory | `sobe/site-header`, `sobe/site-footer` | `EXAMPLE` | Pedagogical layout examples, not client layout ownership. |
| Block Inventory | Missing section/testimonial/team/pricing blocks | `SANDBOX` | They do not exist in demo and are follow-up build work, not migration work. |
| Patterns | Header layout 1/2/3 patterns, footer layout 2 pattern, pattern allowlist enforcement | `PLATFORM` | Hidden layout patterns and validation are shared infrastructure. |
| Patterns | Homepage showcase pattern | `SANDBOX` | Demo content composition. |
| Theme JSON Build | Built theme.json redirection, Vite theme JSON plugin, post-build injection, palette extraction, font size/family/layout injection | `PLATFORM` | Editor parity and token propagation are platform build infrastructure. |
| Translations / i18n | Textdomain config, PHP/Blade translation usage, block editor translation usage | `PLATFORM` | Standardize all strings to `sobe`. |
| Translations / i18n | Translation npm scripts | `PLATFORM` | Keep workflow scripts. |
| Translations / i18n | POT output target / `resources/lang/` | `SANDBOX` | Deferred low-priority implementation decision; not a blocker. |
| Asset Pipeline | Vite entrypoints, Laravel Vite plugin, Roots WordPress plugin, React JSX support, bundle size budget, editor asset injection, asset aliases, public build theme.json, block module output | `PLATFORM` | Build and delivery infrastructure. |
| Service Providers | Acorn application boot, Theme service provider, Composer autoload PSR-4 | `PLATFORM` | Runtime boot infrastructure. |
| Setup Hooks | Theme support cleanup, menus, core supports, HTML5 supports, configured image sizes, excerpt filters, page/post meta, font preload/inline hooks, dark toggle shortcode, sidebar registration, function include list | `PLATFORM` | Generic WP setup. Font values need neutral defaults or removable file checks. |
| Setup Hooks | Product brand taxonomy | `PLATFORM` | Register behind `apply_filters('sobe/register_product_brand', true)`. Empty taxonomy has zero client cost. |
| Setup Hooks | YITH wishlist fallback shortcode | `PLATFORM-WITH-HOOKS` | Keep as one adapter, but wishlist surface must be plugin-agnostic. |
| Security Baseline | Head cleanup, XML-RPC disabled, nonce usage, sanitization/escaping | `PLATFORM` | Shared hardening and hygiene. |
| Security Baseline | REST API access control, Store API public routes, search endpoint public route | `PLATFORM-WITH-HOOKS` | Public route allowlist must be filterable as platform features add routes. |
| Testing & Linting | Jest config, block metadata/save tests, PHPStan config, Composer analyse, CI workflow, pattern checker, environment requirements | `PLATFORM` | Shared quality gates. |
| Testing & Linting | Filter store tests, filter URL utility tests | `PLATFORM` | Move with catalog filter/load-more infrastructure. |
| Testing & Linting | Formatting/linting notes | `PLATFORM` | Keep tooling observation; Pass 3 should decide whether to add explicit scripts separately. |

Bucket count from the classification above: `PLATFORM` 129, `PLATFORM-WITH-HOOKS` 82, `EXAMPLE` 17, `SANDBOX` 7.

### Hook Contracts

Hook naming uses slash-style WordPress hook names under the `sobe/` namespace. Filters return their first parameter. Actions return nothing.

#### Side Cart

| Concern | Hook | Type | Parameters | Return | Example client use |
|---|---|---|---|---|---|
| Enable/disable side cart | `sobe/side_cart/enabled` | filter | `bool $enabled` | `bool` | Disable for wholesale users. |
| Open event detail | `sobe/side_cart/open_detail` | filter | `array $detail`, `string $source` | `array` | Add analytics source metadata. |
| Close event detail | `sobe/side_cart/close_detail` | filter | `array $detail` | `array` | Add reason such as `escape`, `backdrop`, `checkout`. |
| Cart items data | `sobe/side_cart/items` | filter | `array $items`, `WC_Cart $cart` | `array` | Add gift-wrap metadata per item. |
| Cart content partial | `sobe/side_cart/content_view` | filter | `string $view`, `WC_Cart $cart` | Blade view name | Swap `partials.side-cart-content` for a client partial. |
| Fragments | `sobe/side_cart/fragments` | filter | `array $fragments`, `WC_Cart $cart` | `array` | Add a header subtotal fragment. |
| Refresh response HTML | `sobe/side_cart/refresh_html` | filter | `string $html`, `WC_Cart $cart` | `string` | Wrap returned HTML in client markup. |
| After refresh | `sobe/side_cart/refreshed` | action | `WC_Cart $cart`, `array $context` | none | Track cart refresh metrics. |

```php
add_filter('sobe/side_cart/items', function (array $items, WC_Cart $cart): array {
    foreach ($items as &$item) {
        $item['delivery_badge'] = 'Ships in 2 business days';
    }
    return $items;
}, 10, 2);
```

#### Search Modal

| Concern | Hook | Type | Parameters | Return | Example client use |
|---|---|---|---|---|---|
| Search post types | `sobe/search/post_types` | filter | `array $postTypes`, `WP_REST_Request $request` | `array` | Add `portfolio` or remove `page`. |
| Query args | `sobe/search/query_args` | filter | `array $queryArgs`, `string $query`, `int $limit`, `WP_REST_Request $request` | `array` | Prioritize products or add meta query. |
| Result item | `sobe/search/result` | filter | `array $result`, `WP_Post $post` | `array|null` | Add SKU, badge, or return `null` to omit. |
| Results list | `sobe/search/results` | filter | `array $results`, `WP_REST_Request $request` | `array` | Reorder or append promoted results. |
| Overlay view | `sobe/search/overlay_view` | filter | `string $view` | Blade view name | Swap the modal shell. |
| Result render view | `sobe/search/result_view` | filter | `string $view`, `array $result` | Blade view name | Use custom result cards. |
| Runtime params | `sobe/search/params` | filter | `array $params` | `array` | Change limit, placeholder, endpoint namespace. |

```php
add_filter('sobe/search/post_types', fn (array $types) => array_merge($types, ['case_study']));
add_filter('sobe/search/result', function (array $result, WP_Post $post): ?array {
    if ($post->post_type === 'case_study') {
        $result['type_label'] = 'Case Study';
    }
    return $result;
}, 10, 2);
```

#### Wishlist Toggle

The platform surface is plugin-agnostic. YITH is one adapter, not the contract.

| Concern | Hook | Type | Parameters | Return | Example client use |
|---|---|---|---|---|---|
| Wishlist availability | `sobe/wishlist/enabled` | filter | `bool $enabled`, `int|null $productId` | `bool` | Disable wishlist for B2B catalog. |
| Provider selection | `sobe/wishlist/provider` | filter | `string|null $provider` | `string|null` | Return `yith`, `ti`, `custom`, or `null`. |
| Toggle state | `sobe/wishlist/is_active` | filter | `bool $active`, `int $productId`, `int $userId` | `bool` | Read from custom wishlist table. |
| Toggle URL/data | `sobe/wishlist/toggle_data` | filter | `array $data`, `int $productId` | `array` | Provide AJAX endpoint and nonce. |
| Toggle HTML | `sobe/wishlist/toggle_html` | filter | `string $html`, `int $productId`, `array $data` | `string` | Render plugin-specific button. |
| After toggle | `sobe/wishlist/toggled` | action | `int $productId`, `bool $active`, `int $userId` | none | Analytics or CRM sync. |

```php
add_filter('sobe/wishlist/provider', fn () => class_exists('YITH_WCWL') ? 'yith' : null);
add_filter('sobe/wishlist/toggle_html', function (string $html, int $productId): string {
    return shortcode_exists('yith_wcwl_add_to_wishlist')
        ? do_shortcode('[yith_wcwl_add_to_wishlist product_id="'.$productId.'"]')
        : $html;
}, 10, 2);
```

#### Catalog Filters

| Concern | Hook | Type | Parameters | Return | Example client use |
|---|---|---|---|---|---|
| Filter groups | `sobe/catalog_filters/groups` | filter | `array $groups`, `array $context` | `array` | Add material, availability, or remove price. |
| Brand taxonomy | `sobe/catalog_filters/brand_taxonomy` | filter | `string $taxonomy` | `string` | Use `pa_brand` instead of `product_brand`. |
| Query state before query | `sobe/catalog_filters/state` | filter | `array $state`, `WP_REST_Request|array $request` | `array` | Normalize custom params. |
| Query args | `sobe/catalog_filters/query_args` | filter | `array $queryArgs`, `array $state` | `array` | Add stock visibility or custom ordering. |
| Term counts | `sobe/catalog_filters/term_counts` | filter | `array $counts`, `array $queryArgs` | `array` | Replace counts from external index. |
| Swatch value | `sobe/catalog_filters/swatch_value` | filter | `?string $value`, `WP_Term $term`, `string $attribute` | `?string` | Resolve colors from client term meta. |
| Result HTML | `sobe/catalog_filters/results_html` | filter | `string $html`, `WP_Query $query`, `array $state` | `string` | Wrap product cards or add empty state. |
| Pagination HTML | `sobe/catalog_filters/pagination_html` | filter | `string $html`, `WP_Query $query`, `array $state` | `string` | Swap pagination component. |
| AJAX response | `sobe/catalog_filters/response` | filter | `array $response`, `WP_Query $query`, `array $state` | `array` | Add analytics metadata. |
| Block view | `sobe/catalog_filters/view` | filter | `string $view`, `array $attributes` | Blade view name | Replace filter markup while keeping handler. |

```php
add_filter('sobe/catalog_filters/groups', function (array $groups): array {
    $groups['availability'] = [
        'label' => 'Availability',
        'type' => 'checkbox',
        'options' => ['in_stock' => 'In stock'],
    ];
    return $groups;
});

add_filter('sobe/catalog_filters/query_args', function (array $args, array $state): array {
    if (! empty($state['availability'])) {
        $args['meta_query'][] = ['key' => '_stock_status', 'value' => 'instock'];
    }
    return $args;
}, 10, 2);
```

#### Shop Loop

| Concern | Hook | Type | Parameters | Return | Example client use |
|---|---|---|---|---|---|
| Columns | `sobe/shop_loop/columns` | filter | `int $columns`, `string $breakpoint` | `int` | Use 5 columns for large catalogs. |
| Products per page | `sobe/shop_loop/per_page` | filter | `int $perPage`, `array $context` | `int` | Increase category pages only. |
| Main query args | `sobe/shop_loop/query_args` | filter | `array $queryArgs`, `array $context` | `array` | Exclude hidden collection. |
| Product card view | `sobe/shop_loop/product_card_view` | filter | `string $view`, `WC_Product $product` | Blade view name | Swap product card partial. |
| Product card data | `sobe/shop_loop/product_card_data` | filter | `array $data`, `WC_Product $product` | `array` | Add badges or brand label. |
| Before/after card | `sobe/shop_loop/before_product_card`, `sobe/shop_loop/after_product_card` | action | `WC_Product $product`, `array $context` | none | Inject tracking wrappers or badges. |

```php
add_filter('sobe/shop_loop/product_card_data', function (array $data, WC_Product $product): array {
    $data['badge'] = $product->is_featured() ? 'Featured' : '';
    return $data;
}, 10, 2);
```

#### PDP Gallery

| Concern | Hook | Type | Parameters | Return | Example client use |
|---|---|---|---|---|---|
| Gallery enabled | `sobe/pdp_gallery/enabled` | filter | `bool $enabled`, `WC_Product $product` | `bool` | Fall back to native gallery for a product type. |
| Image IDs | `sobe/pdp_gallery/image_ids` | filter | `array $imageIds`, `WC_Product $product` | `array` | Add lifestyle image attachments. |
| Gallery view | `sobe/pdp_gallery/view` | filter | `string $view`, `WC_Product $product` | Blade view name | Replace Swiper gallery shell. |
| Gallery settings | `sobe/pdp_gallery/settings` | filter | `array $settings`, `WC_Product $product` | `array` | Change aspect ratio or Swiper options. |
| After gallery render | `sobe/pdp_gallery/after` | action | `WC_Product $product`, `array $imageIds` | none | Add trust badges below gallery. |

```php
add_filter('sobe/pdp_gallery/settings', function (array $settings): array {
    $settings['aspect_ratio'] = '4 / 5';
    return $settings;
});
```

#### PDP Tabs / Accordions

| Concern | Hook | Type | Parameters | Return | Example client use |
|---|---|---|---|---|---|
| Tabs | `sobe/pdp_tabs/tabs` | filter | `array $tabs`, `WC_Product $product` | `array` | Add care instructions. |
| Tab title | `sobe/pdp_tabs/title` | filter | `string $title`, `string $key`, `array $tab`, `WC_Product $product` | `string` | Rename shipping tab. |
| Tab content | `sobe/pdp_tabs/content` | filter | `string $content`, `string $key`, `array $tab`, `WC_Product $product` | `string` | Replace copied shipping text. |
| Accordion view | `sobe/pdp_tabs/accordion_view` | filter | `string $view`, `array $tabs`, `WC_Product $product` | Blade view name | Use custom accordion markup. |

```php
add_filter('sobe/pdp_tabs/tabs', function (array $tabs, WC_Product $product): array {
    $tabs['care'] = [
        'title' => 'Care',
        'priority' => 55,
        'callback' => fn () => print '<p>Wipe clean with a soft cloth.</p>',
    ];
    return $tabs;
}, 10, 2);
```

#### Related Products

| Concern | Hook | Type | Parameters | Return | Example client use |
|---|---|---|---|---|---|
| Related args | `sobe/related_products/args` | filter | `array $args`, `WC_Product $product` | `array` | Change count or columns. |
| Related products | `sobe/related_products/products` | filter | `array $products`, `WC_Product $product` | `array` | Use curated related IDs. |
| Section heading | `sobe/related_products/heading` | filter | `string $heading`, `WC_Product $product` | `string` | Rename heading. |
| Section view | `sobe/related_products/view` | filter | `string $view`, `array $products`, `WC_Product $product` | Blade view name | Swap section markup. |

```php
add_filter('sobe/related_products/args', fn (array $args) => array_merge($args, ['posts_per_page' => 8]));
```

#### Mini-Cart Count Fragment

| Concern | Hook | Type | Parameters | Return | Example client use |
|---|---|---|---|---|---|
| Count value | `sobe/mini_cart/count` | filter | `int $count`, `WC_Cart $cart` | `int` | Count unique lines instead of quantities. |
| Count HTML | `sobe/mini_cart/count_html` | filter | `string $html`, `int $count`, `WC_Cart $cart` | `string` | Render custom badge markup. |
| Count fragments | `sobe/mini_cart/count_fragments` | filter | `array $fragments`, `int $count`, `WC_Cart $cart` | `array` | Add mobile header selector. |

```php
add_filter('sobe/mini_cart/count', fn (int $count, WC_Cart $cart) => count($cart->get_cart()), 10, 2);
```

### Reviews Slider Recommendation

Options:

- Option A: keep one `sobe/reviews-slider`, gate `auto`/`products` modes behind WooCommerce checks.
- Option B: split into generic `sobe/testimonial-slider` plus WC-coupled `sobe/reviews-slider`.
- Option C: classify as `EXAMPLE` and have clients fork.

Recommendation: Option B.

Reasoning: the current block already contains two products in one API: a generic testimonial slider and a WooCommerce review aggregator. Keeping both in one public block makes every client inherit WC-specific controls even when they only need testimonials. Classifying it as an example wastes a production-ready generic pattern. Split preserves the manual testimonial UX as a universal block while keeping the WC review mode honest as commerce infrastructure.

Tradeoffs:

- More implementation work than Option A because attributes, editor UI, and views split.
- Cleaner long-term package dependency and content model.
- Lets `sobe/testimonial-slider` remain usable without WooCommerce while `sobe/reviews-slider` can depend on product/review hooks.

Pending decision: approve Option B before Pass 3 finalizes file operations. Until approved, this audit counts the current source artifact as `PLATFORM-WITH-HOOKS` because at least one promoted public block should come from it.

### Catalog Filters Recommendation

Classification: `PLATFORM-WITH-HOOKS`.

Reasoning: catalog filtering is too central to the platform WC layer to leave as an example. It is significant design work, but the right extension model is clear: expose filter group definitions, query args, term counts, swatch values, result HTML, pagination HTML, and AJAX response data. Clients should not rebuild catalog filtering from scratch just to add one facet or alter a query.

Risk: this becomes one of the hardest platform contracts to change. Pass 3 should include focused tests around query arg generation, URL state, AJAX response shape, and filter group extension.

### product_brand-Related Classifications

| Item | Classification | Reasoning |
|---|---:|---|
| `product_brand` taxonomy | `PLATFORM` | Shared structure with client-owned terms. Register behind `sobe/register_product_brand`. |
| `brand-carousel` | `PLATFORM-WITH-HOOKS` | Useful generic brand showcase now that taxonomy is platform. Needs hooks for taxonomy, term query, logo meta, item data, and render view. |
| `our-brands` | `PLATFORM-WITH-HOOKS` | Generic brand directory. Needs hooks for taxonomy, grouping, alphabet index, term data, and template. |
| `product-feature` brand label | `PLATFORM-WITH-HOOKS` | The block is a generic product showcase; brand label should be data-driven and filterable. |
| `catalog-filters` brand facet | `PLATFORM-WITH-HOOKS` | Brand facet is a first-class filter group, but taxonomy slug, visibility, counts, and labels must be filterable. |

### Library Audit

| Library | Used by public features/blocks | Move to main? | Reason |
|---|---|---:|---|
| `alpinejs` | App shell, dark mode, nav, search modal, side-cart, toast container | yes | Core app shell dependency. Already public. |
| `@alpinejs/focus` | Search modal, mobile nav traps, side-cart focus trap | yes | Promoted overlay/drawer accessibility requires it. |
| `swiper` | Product carousel, PDP gallery, product-categories-grid | yes | Product-carousel already makes it public; stays public. |
| `gsap` | Animation bus, sticky header, Lenis ticker integration, catalog filter refresh | yes | Promoted app shell and AJAX animation refresh use it. |
| `lenis` | Smooth scrolling, side-cart scroll locking coordination | yes | Promoted app shell uses it. Keep guarded by reduced-motion and viewport checks. |
| `nouislider` | Catalog filters price range | yes | Catalog filters are `PLATFORM-WITH-HOOKS`, so dependency moves. |
| `@babel/core`, `@babel/preset-env`, `@babel/preset-react`, `babel-jest`, `jest`, `jest-environment-node` | Block/editor tests and JSX transform | yes | Dev tooling for public block library. |
| `@roots/vite-plugin`, `@tailwindcss/vite`, `@vitejs/plugin-react`, `@wordpress/scripts`, `laravel-vite-plugin`, `tailwindcss`, `vite` | Asset pipeline, block editor, theme.json build | yes | Existing platform build stack. |
| `roots/acorn`, `generoi/sage-woocommerce` | Theme runtime and Blade WooCommerce integration | yes | Existing PHP runtime stack. |
| `laravel/pint`, `php-stubs/woocommerce-stubs`, `phpstan/phpstan`, `szepeviktor/phpstan-wordpress` | Analysis/format tooling | yes | Shared quality tooling. |
| External plugin globals: PhotoSwipe, Yoast, Rank Math, AIOSEO, SEOPress, Relevanssi, YITH | Optional integrations only | no package dependency | Detect at runtime; do not add public package/composer requirements. |

No demo package library is used only by `SANDBOX`-classified blocks after this Pass 2 recommendation. If reviews-slider Option C is chosen later, its custom view script has no extra package dependency, so library movement is unaffected.

### Current Main vs Demo Preference Notes

Demo wins broadly, with these Pass 3 implementation notes:

- Prefer current main's neutral token values as the base replacement for demo's brand color values.
- Prefer current main's textdomain direction (`sobe`) over demo's mixed `sage`/`sobe`.
- Prefer current main block scaffold/checker improvements if they are demonstrably cleaner than demo's older scripts.
- Do not preserve current main's stripped hero/product-carousel behavior; demo feature depth is the intended source, except for broken WebGL and explicitly brand-specific hardcoding.

### Pass 2 Checkpoint

Pass 2 classification is complete for review. Pass 3 migration planning is intentionally not started.

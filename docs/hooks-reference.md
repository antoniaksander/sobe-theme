# Hooks Reference

Hooks use the `sobe/<feature>/<action>` namespace. Filters return a value. Actions perform side effects.

## Product Brand

| Hook | Type | Parameters | Return |
|------|------|------------|--------|
| `sobe/product_brand/register` | filter | `bool $enabled` | `bool` |
| `sobe/catalog_filters/brand_taxonomy` | filter | `string $taxonomy` | `string` taxonomy slug |

Example:

```php
add_filter('sobe/catalog_filters/brand_taxonomy', fn () => 'product_brand');
```

## Block Registration

| Hook | Type | Parameters | Return |
|------|------|------------|--------|
| `sobe/blocks/allowed_types` | filter | `array $allowed, mixed $originalAllowedBlocks` | Allowed block names |

Block folders are listed in `resources/blocks/blocks-manifest.json`. Manifest
keys are folder slugs. Each entry requires `category` and may include `name`,
the full block name used by tooling and tests. When `name` is omitted, tooling
defaults to `sobe/<slug>`. Runtime registration still reads the block name from
the block's `block.json`.

Example:

```php
add_filter('sobe/blocks/allowed_types', function (array $allowed): array {
    $allowed[] = 'acf/client-callout';
    return $allowed;
});
```

## Layout Shell

| Hook | Type | Parameters | Return |
|------|------|------------|--------|
| `sobe/layout/block_name` | filter | `string $blockName, string $type, string $variant` | Dynamic block name for the layout shell |

The default shell blocks are `sobe/site-header` and `sobe/site-footer`. Client
prefix changes do not rename them. Override this only when the client fork
deliberately registers replacement shell blocks.

Example:

```php
add_filter('sobe/layout/block_name', function (string $blockName, string $type): string {
    return $type === 'header' ? 'client/site-header' : $blockName;
}, 10, 2);
```

## Navigation

| Hook | Type | Parameters | Return |
|------|------|------------|--------|
| `sobe/navigation/fallback_html` | filter | `string $html, array $args` | Header navigation fallback HTML |

The header renders the assigned `primary_navigation` menu when present. If no
menu is assigned, it falls back to a page list, or a Home link when there are no
pages.

## Footer

| Hook | Type | Parameters | Return |
|------|------|------------|--------|
| `sobe/footer/fallback_links` | filter | `array $links` | Footer fallback links |

Fallback links render only when no `Footer Navigation` menu is assigned and the
`Footer` widget area is empty. Each link item should include `label` and `url`.

## Hero

| Hook | Type | Parameters | Return |
|------|------|------------|--------|
| `sobe/hero/data` | filter | `array $data, array $attributes` | Resolved render data |
| `sobe/hero/background_view` | filter | `string $view, array $data, array $attributes` | Blade view name |
| `sobe/hero/cta_view` | filter | `string $view, array $data, array $attributes` | Blade view name |
| `sobe/hero/view` | filter | `string $view, array $data, array $attributes` | Full replacement Blade view name |

Example:

```php
add_filter('sobe/hero/data', function (array $data): array {
    $data['darkOverlay'] = true;
    return $data;
});
```

## Shop Loop

| Hook | Type | Parameters | Return |
|------|------|------------|--------|
| `sobe/shop_loop/columns` | filter | `int $columns, string $breakpoint` | Column count |
| `sobe/shop_loop/per_page` | filter | `int $perPage, array $context` | Products per page |
| `sobe/shop_loop/query_args` | filter | `array $queryArgs, array $context` | `WP_Query` args |
| `sobe/shop_loop/product_card_view` | filter | `string $view, WC_Product $product` | Blade view name |
| `sobe/shop_loop/product_card_data` | filter | `array $data, WC_Product $product` | Product card data |
| `sobe/shop_loop/before_product_card` | action | `WC_Product $product, array $context` | None |
| `sobe/shop_loop/after_product_card` | action | `WC_Product $product, array $context` | None |

Example:

```php
add_filter('sobe/shop_loop/per_page', function (int $perPage, array $context): int {
    return ($context['context'] ?? '') === 'shop' ? 24 : $perPage;
}, 10, 2);
```

## Catalog Filters

| Hook | Type | Parameters | Return |
|------|------|------------|--------|
| `sobe/catalog_filters/data` | filter | `array $data, array $attrs` | Filter block data |
| `sobe/catalog_filters/state` | filter | `array $state, array $source` | Active filter state |
| `sobe/catalog_filters/query_args` | filter | `array $queryArgs, array $state` | Product query args |
| `sobe/catalog_filters/results_html` | filter | `string $html, WP_Query $query, array $state` | Product results HTML |
| `sobe/catalog_filters/pagination_html` | filter | `string $html, WP_Query $query, array $state` | Pagination HTML |
| `sobe/catalog_filters/response` | filter | `array $response, WP_Query $query, array $state` | AJAX response data |
| `sobe/catalog_filters/term_counts` | filter | `array $counts, array $baseQueryArgs` | Term count data |
| `sobe/catalog_filters/swatch_value` | filter | `?string $value, WP_Term $term, string $attributeName` | Swatch value |
| `sobe_swatch_value` | filter | `?string $value, WP_Term $term, string $attributeName` | Legacy swatch value |

Example:

```php
add_filter('sobe/catalog_filters/query_args', function (array $args, array $state): array {
    $args['meta_query'][] = ['key' => '_stock_status', 'value' => 'instock'];
    return $args;
}, 10, 2);
```

## PDP Gallery

| Hook | Type | Parameters | Return |
|------|------|------------|--------|
| `sobe/pdp_gallery/enabled` | filter | `bool $enabled, WC_Product $product` | `bool` |
| `sobe/pdp_gallery/settings` | filter | `array $settings, WC_Product $product` | Gallery JS settings |
| `sobe/pdp_gallery/image_ids` | filter | `array $imageIds, WC_Product $product` | Attachment IDs |
| `sobe/pdp_gallery/view` | filter | `string $view, WC_Product $product` | Gallery Blade view |
| `sobe/pdp_gallery/after` | action | `WC_Product $product, array $imageIds` | None |

Example:

```php
add_filter('sobe/pdp_gallery/settings', function (array $settings): array {
    $settings['aspectRatio'] = '4 / 5';
    return $settings;
});
```

## PDP Tabs And Related Products

| Hook | Type | Parameters | Return |
|------|------|------------|--------|
| `sobe/pdp_tabs/tabs` | filter | `array $tabs, WC_Product $product` | WooCommerce tab array |
| `sobe/pdp_tabs/accordion_view` | filter | `string $view, array $tabs, WC_Product $product` | Accordion Blade view |
| `sobe/pdp_tabs/title` | filter | `string $title, string $key, array $tab, WC_Product $product` | Tab title |
| `sobe/pdp_tabs/content` | filter | `string $content, string $key, array $tabOrProduct, ?WC_Product $product` | Tab content |
| `sobe_shipping_info_text` | filter | `string $content` | Legacy shipping info copy |
| `sobe/related_products/args` | filter | `array $args, ?WC_Product $product` | Related products args |
| `sobe/related_products/products` | filter | `array $productIds, ?WC_Product $product` | Related product IDs |
| `sobe/related_products/heading` | filter | `string $heading, WC_Product $product` | Heading text |

Example:

```php
add_filter('sobe/pdp_tabs/tabs', function (array $tabs): array {
    unset($tabs['reviews']);
    return $tabs;
});
```

## Side-Cart And Mini-Cart

| Hook | Type | Parameters | Return |
|------|------|------------|--------|
| `sobe/side_cart/enabled` | filter | `bool $enabled` | `bool` |
| `sobe/side_cart/content_view` | filter | `string $view, ?WC_Cart $cart` | Blade view name |
| `sobe/side_cart/items` | filter | `array $items, ?WC_Cart $cart` | Cart items |
| `sobe/side_cart/fragments` | filter | `array $fragments, ?WC_Cart $cart` | Woo fragments |
| `sobe/side_cart/open_detail` | filter | `array $detail, string $source` | Browser event detail |
| `sobe/side_cart/close_detail` | filter | `array $detail` | Browser event detail |
| `sobe/side_cart/refresh_html` | filter | `string $html, WC_Cart $cart` | AJAX refresh HTML |
| `sobe/side_cart/refreshed` | action | `WC_Cart $cart, array $context` | None |
| `sobe/mini_cart/count` | filter | `int $count, ?WC_Cart $cart` | Cart count |
| `sobe/mini_cart/count_html` | filter | `string $html, int $count, ?WC_Cart $cart` | Count HTML |
| `sobe/mini_cart/count_fragments` | filter | `array $fragments, int $count, ?WC_Cart $cart` | Count fragments |

Example:

```php
add_filter('sobe/side_cart/enabled', fn (bool $enabled): bool => is_checkout() ? false : $enabled);
```

## Search

| Hook | Type | Parameters | Return |
|------|------|------------|--------|
| `sobe/search/post_types` | filter | `array $postTypes, WP_REST_Request $request` | Post types |
| `sobe/search/query_args` | filter | `array $args, string $query, int $limit, WP_REST_Request $request` | `WP_Query` args |
| `sobe/search/result` | filter | `array $result, WP_Post $post` | Single result |
| `sobe/search/results` | filter | `array $results, WP_REST_Request $request` | Result list |
| `sobe/search/params` | filter | `array $params` | Frontend params |
| `sobe/search/overlay_view` | filter | `string $view` | Overlay Blade view |

Example:

```php
add_filter('sobe/search/post_types', fn () => ['product']);
```

## Wishlist

| Hook | Type | Parameters | Return |
|------|------|------------|--------|
| `sobe/wishlist/enabled` | filter | `bool $enabled, ?int $productId` | `bool` |
| `sobe/wishlist/provider` | filter | `?string $provider` | Provider key |
| `sobe/wishlist/is_active` | filter | `bool $active, int $productId, int $userId` | `bool` |
| `sobe/wishlist/toggle_data` | filter | `array $data, int $productId` | Toggle data |
| `sobe/wishlist/toggle_html` | filter | `string $html, int $productId, array $data` | Toggle HTML |

Example:

```php
add_filter('sobe/wishlist/provider', fn () => 'custom');
add_filter('sobe/wishlist/toggle_html', function (string $html, int $productId): string {
    return '<button data-product-id="'.esc_attr((string) $productId).'">Save</button>';
}, 10, 2);
```

## Product Feature

Hyphenated hooks are preferred. Underscore hooks remain as compatibility shims for data resolution.

| Hook | Type | Parameters | Return |
|------|------|------------|--------|
| `sobe/product-feature/product_id` | filter | `int $productId, array $attrs` | Product ID |
| `sobe/product-feature/brand_taxonomy` | filter | `string $taxonomy, WC_Product $product, array $attrs` | Taxonomy slug |
| `sobe/product-feature/data` | filter | `array $data, WC_Product $product, array $attrs` | Render data |
| `sobe/product-feature/view` | filter | `string $view, array $data, array $attrs` | Blade view |
| `sobe/product_feature/product_id` | filter | `int $productId, array $attrs` | Compatibility |
| `sobe/product_feature/brand_taxonomy` | filter | `string $taxonomy, WC_Product $product, array $attrs` | Compatibility |
| `sobe/product_feature/data` | filter | `array $data, WC_Product $product, array $attrs` | Compatibility |

## Other Block Hooks

| Hook | Type | Parameters | Return |
|------|------|------------|--------|
| `sobe/brand_carousel/items` | filter | `array $items, array $attributes` | Brand carousel items |
| `sobe/brand_carousel/view` | filter | `string $view, array $items, array $attributes` | Blade view |
| `sobe/our_brands/grouped` | filter | `array $grouped, array $attributes` | Grouped terms |
| `sobe/our_brands/view` | filter | `string $view, array $grouped, array $attributes` | Blade view |
| `sobe/product_categories_grid/categories` | filter | `array $categories, array $attributes` | Category data |
| `sobe/product_categories_grid/view` | filter | `string $view, array $categories, array $attributes` | Blade view |
| `sobe/reviews_slider/slides` | filter | `array $slides, array $attributes` | Review slides |
| `sobe/reviews_slider/view` | filter | `string $view, array $slides, array $attributes` | Blade view |

## SEO And Security

| Hook | Type | Parameters | Return |
|------|------|------------|--------|
| `sobe/seo/disable_baseline` | filter | `bool $disabled` | Disable baseline SEO output |
| `sobe/security/public_routes` | filter | `array $routes` | Public REST route allowlist |

# Block Authoring

Use this with [client-boundary.md](client-boundary.md), the Block Registration
section in [hooks-reference.md](hooks-reference.md#block-registration), and
[merge-strategy.md](merge-strategy.md).

## Namespace And Folder Convention

Platform blocks use the `sobe/*` namespace and live under
`resources/blocks/sobe/`. Client-namespaced blocks in client forks use the
client prefix and live under `resources/blocks/{client-prefix}/`.

One block name maps to one folder path:
`resources/blocks/{namespace}/{slug}`.

## Manifest Entry Shape

`resources/blocks/blocks-manifest.json` uses path-style keys relative to
`resources/blocks/`. Each entry requires `category`; `name` is optional and
defaults to `{namespace}/{slug}` when omitted.

Minimal platform entry:

```json
{
  "sobe/hero": {
    "category": "sobe-general"
  }
}
```

Minimal client entry for reference:

```json
{
  "roxder/hero": {
    "category": "roxder"
  }
}
```

Clients own their client-namespaced blocks and categories in their repos.

## Blade View Location

Block views live at:

```text
resources/views/blocks/{namespace}/{slug}.blade.php
```

## Categories

Platform categories are registered in `app/blocks.php`:

- `sobe-general`
- `sobe-woocommerce`
- `sobe-content`
- `sobe-layout`

New platform categories are added in `app/blocks.php`. Clients may add their
own categories in client repos.

## Runtime Coupling Rule

A block must render meaningfully on its own. Block-level attributes whose
contract depends on theme runtime such as parsers, Alpine shells, or generated
markup elsewhere belong in client repos until that runtime is also
platform-owned.

For example, a `subPanel` style mobile attribute is not portable if the panel
rendering lives outside the block.

## WooCommerce Coupling

Editor data sources tied to WooCommerce, such as product entity records and
product taxonomies, make a block WooCommerce-aware in practice even when the
frontend has a non-Woo fallback. WooCommerce-aware blocks belong in
`sobe-woocommerce`, not in generic categories.

## CSS Naming

WordPress creates wrapper classes as `.wp-block-{namespace}-{slug}`. Block root
classes should be descriptive and not client-branded. Avoid prefixing every
internal class with the namespace; favor the block's functional name, such as
`.hero` or `.product-card`.

### Render context

`app/blocks.php` passes namespace-aware context into every block view, so a
copied block never has to hard-code an upstream class name:

| Variable | Example (`sobe/product-feature`) |
| --- | --- |
| `$blockBaseClass` | `product-feature` |
| `$blockNamespaceClass` | `product-feature--sobe` |
| `$blockNamespace` | `sobe` |
| `$blockSlug` | `product-feature` |
| `$blockName` | `sobe/product-feature` |
| `$block` | the `WP_Block` instance (may be `null`) |

### Copyable root-class pattern

Use a **neutral component class plus a generated namespace modifier**, and keep
inner element classes namespace-neutral (BEM-style):

```php
$componentClass = $blockBaseClass ?? 'product-feature';
$namespaceClass = $blockNamespaceClass ?? "{$componentClass}--sobe";

$wrapperAttrs = get_block_wrapper_attributes([
    'class' => trim("{$componentClass} {$namespaceClass}"),
]);
```

```html
<h2 class="product-feature__heading ...">…</h2>
```

A client fork that copies the block to its own namespace then renders
`product-feature product-feature--{client}` automatically, targets brand styles
at `.product-feature--{client} .product-feature__heading`, and carries no stale
upstream internals. `sobe/product-feature` is the reference implementation.

## Tests

Manifest-wide metadata and save tests run automatically. Per-block tests are
optional and reserved for behavior with non-trivial regression risk.

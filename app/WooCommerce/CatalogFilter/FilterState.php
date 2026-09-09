<?php

namespace App\WooCommerce\CatalogFilter;

/**
 * Normalized, validated catalog filter state — the single internal
 * representation shared by direct GET, filter AJAX, and load-more. Never
 * constructed directly from raw request data; always produced by
 * FilterStateParser::fromArray(), which is the one place allowlisting,
 * validation, deduplication, and size-capping happen.
 *
 * This is mutable *state* (what the user selected), as opposed to
 * CatalogContext, which is the immutable base the state may narrow.
 */
final class FilterState
{
    public const PRICE_TYPE_ALL = 'all';

    public const PRICE_TYPE_ON_SALE = 'on_sale';

    public const PRICE_TYPE_FULL_PRICE = 'full_price';

    public const OPERATOR_AND = 'AND';

    public const OPERATOR_OR = 'OR';

    /**
     * @param  string[]  $categorySlugs
     * @param  string[]  $tagSlugs
     * @param  string[]  $brandSlugs
     * @param  array<string, array{terms: string[], operator: 'AND'|'OR'}>  $attributes  Keyed by full taxonomy name (pa_*).
     * @param  string[]  $stockStatuses
     */
    public function __construct(
        public readonly array $categorySlugs = [],
        public readonly array $tagSlugs = [],
        public readonly array $brandSlugs = [],
        public readonly array $attributes = [],
        public readonly ?float $minPrice = null,
        public readonly ?float $maxPrice = null,
        public readonly string $priceType = self::PRICE_TYPE_ALL,
        public readonly array $stockStatuses = [],
        public readonly string $orderby = 'menu_order',
        public readonly string $order = 'ASC',
        public readonly int $paged = 1,
        public readonly ?string $search = null,
    ) {}

    public function hasAnyFilter(): bool
    {
        return $this->categorySlugs !== []
            || $this->tagSlugs !== []
            || $this->brandSlugs !== []
            || $this->attributes !== []
            || $this->minPrice !== null
            || $this->maxPrice !== null
            || $this->priceType !== self::PRICE_TYPE_ALL
            || $this->stockStatuses !== []
            || ($this->search !== null && $this->search !== '');
    }

    /**
     * Slugs for a given taxonomy from the normalized state — used by the
     * archive-intersection logic to check whether the user also selected the
     * archive's own taxonomy, and by URL serialization.
     *
     * @return string[]
     */
    public function slugsForTaxonomy(string $taxonomy, string $brandTaxonomy): array
    {
        return match (true) {
            $taxonomy === 'product_cat' => $this->categorySlugs,
            $taxonomy === 'product_tag' => $this->tagSlugs,
            $taxonomy === $brandTaxonomy => $this->brandSlugs,
            isset($this->attributes[$taxonomy]) => $this->attributes[$taxonomy]['terms'],
            default => [],
        };
    }

    /**
     * @return array<string, mixed> Plain array shape kept for callers still
     *                              expecting the legacy $state array
     *                              (e.g. sobe/catalog_filters/state consumers).
     */
    public function toArray(): array
    {
        return [
            'product_cat' => $this->categorySlugs,
            'product_tag' => $this->tagSlugs,
            'brand' => $this->brandSlugs,
            'attributes' => $this->attributes,
            'min_price' => $this->minPrice,
            'max_price' => $this->maxPrice,
            'price_type' => $this->priceType,
            'stock_status' => $this->stockStatuses,
            'orderby' => $this->orderby,
            'order' => $this->order,
            'paged' => $this->paged,
            's' => $this->search,
        ];
    }

    public function withPaged(int $paged): self
    {
        return new self(
            $this->categorySlugs,
            $this->tagSlugs,
            $this->brandSlugs,
            $this->attributes,
            $this->minPrice,
            $this->maxPrice,
            $this->priceType,
            $this->stockStatuses,
            $this->orderby,
            $this->order,
            max(1, $paged),
            $this->search,
        );
    }
}

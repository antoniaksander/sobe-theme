<?php

namespace App\WooCommerce\CatalogFilter;

/**
 * Immutable base context a catalog query runs against — the part of the page
 * that a user filter may narrow but must never replace, drop, or widen.
 *
 * One instance describes exactly one of: the shop page, a single product
 * taxonomy archive term, a search, or a client-owned custom listing. This is
 * intentionally a plain, serializable value object (no WordPress calls in the
 * constructor) so it can be built from either a live WP_Query/queried object
 * (direct GET / woocommerce_product_query) or from a JSON payload posted by
 * the frontend (AJAX / load-more), and compared for equality between the two.
 */
final class CatalogContext
{
    public const TYPE_SHOP = 'shop';

    public const TYPE_TAXONOMY = 'taxonomy';

    public const TYPE_SEARCH = 'search';

    public const TYPE_CUSTOM = 'custom';

    /**
     * @param  array<string, mixed>  $custom  Opaque payload for a client-owned adapter
     *                                        (see CustomContextAdapter). Sobe never reads
     *                                        into this beyond passing it to the adapter.
     */
    private function __construct(
        public readonly string $type,
        public readonly ?string $taxonomy,
        public readonly ?string $termSlug,
        public readonly int $termId,
        public readonly ?string $search,
        public readonly array $custom,
    ) {}

    public static function shop(): self
    {
        return new self(self::TYPE_SHOP, null, null, 0, null, []);
    }

    public static function taxonomy(string $taxonomy, string $termSlug, int $termId = 0): self
    {
        return new self(self::TYPE_TAXONOMY, sanitize_key($taxonomy), sanitize_title($termSlug), max(0, $termId), null, []);
    }

    public static function search(string $searchTerm): self
    {
        return new self(self::TYPE_SEARCH, null, null, 0, sanitize_text_field($searchTerm), []);
    }

    /**
     * @param  array<string, mixed>  $payload  Opaque, adapter-defined context.
     */
    public static function custom(array $payload): self
    {
        return new self(self::TYPE_CUSTOM, null, null, 0, null, $payload);
    }

    /**
     * Build from the current global WordPress query state — the direct-GET /
     * initial-render path. Never trusts $_GET for identity, only the queried
     * object itself, so this can't be spoofed into claiming a different
     * archive than WordPress actually resolved.
     */
    public static function fromCurrentQuery(): self
    {
        if (function_exists('is_search') && is_search()) {
            return self::search((string) get_search_query());
        }

        if (function_exists('is_product_taxonomy') && is_product_taxonomy()) {
            $term = get_queried_object();
            if ($term instanceof \WP_Term && $term->taxonomy !== '' && $term->slug !== '') {
                return self::taxonomy($term->taxonomy, $term->slug, (int) $term->term_id);
            }
        }

        return self::shop();
    }

    /**
     * Build from a JSON-decoded payload posted by the frontend (the shape
     * `catalog-filters/view.js` already sends as `filter_context`, and that
     * `shop-load-more.js` must send too — see FilterStateParser::context()).
     * Unlike fromCurrentQuery(), this trusts client input only for *which*
     * taxonomy/term the browser believes it's on; callers that need to
     * defend against a forged context (e.g. claiming a taxonomy archive that
     * isn't actually the current request) should validate the term exists
     * and is public before trusting it for anything privileged. Catalog
     * filtering itself is read-only, so a forged context can at most narrow
     * to the wrong (but still public) term — it cannot widen visibility.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $contextType = sanitize_key((string) ($payload['contextType'] ?? ''));

        if ($contextType === self::TYPE_SEARCH) {
            return self::search((string) ($payload['search'] ?? ''));
        }

        if ($contextType === self::TYPE_TAXONOMY) {
            $taxonomy = sanitize_key((string) ($payload['archiveTaxonomy'] ?? ''));
            $termSlug = sanitize_title((string) ($payload['archiveTerm'] ?? ''));
            $termId = (int) ($payload['queriedObjectId'] ?? 0);

            if ($taxonomy !== '' && $termSlug !== '' && taxonomy_exists($taxonomy)) {
                return self::taxonomy($taxonomy, $termSlug, $termId);
            }

            // Malformed taxonomy context must fail closed to "shop", never to
            // "no context" (which would mean no narrowing at all) or to a
            // half-populated taxonomy context a consumer might mishandle.
            return self::shop();
        }

        if ($contextType === self::TYPE_CUSTOM) {
            return self::custom((array) ($payload['custom'] ?? []));
        }

        return self::shop();
    }

    public function isTaxonomy(): bool
    {
        return $this->type === self::TYPE_TAXONOMY;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'contextType' => $this->type,
            'archiveTaxonomy' => $this->taxonomy ?? '',
            'archiveTerm' => $this->termSlug ?? '',
            'queriedObjectId' => $this->termId,
            'search' => $this->search ?? '',
            'custom' => $this->custom,
        ];
    }
}

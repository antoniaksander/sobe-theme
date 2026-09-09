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
     * A posted context that claimed to be TYPE_TAXONOMY but failed
     * validation (unknown taxonomy, unknown term, or a term/taxonomy-ID
     * mismatch between what was posted and what actually exists). This is
     * deliberately its own type, never silently reinterpreted as TYPE_SHOP —
     * falling back to "shop" would widen a request that was supposed to be
     * scoped to one archive term into an unscoped catalog-wide query, which
     * is exactly the failure mode the immutable-context contract exists to
     * prevent. QueryTransformer treats this as "must match zero results."
     */
    public const TYPE_INVALID = 'invalid';

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

    public static function invalid(): self
    {
        return new self(self::TYPE_INVALID, null, null, 0, null, []);
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
     * `shop-load-more.js` must send too).
     *
     * A payload that does not claim TYPE_TAXONOMY (absent, "shop", "search",
     * "custom", or garbage) is treated as a legitimate non-taxonomy request —
     * that's the normal shape for the shop page or search. A payload that
     * *does* claim TYPE_TAXONOMY is validated against the real taxonomy/term
     * (see taxonomyFromArray()); if that validation fails, this returns
     * TYPE_INVALID, never TYPE_SHOP — a request that claimed to be scoped to
     * one archive term must never silently become an unscoped shop query
     * just because its context payload was malformed, stale, or forged.
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
            return self::taxonomyFromArray($payload);
        }

        if ($contextType === self::TYPE_CUSTOM) {
            return self::custom((array) ($payload['custom'] ?? []));
        }

        return self::shop();
    }

    /**
     * Validates a claimed taxonomy context against the real term: the
     * taxonomy must exist, a term with the posted slug must exist in it, and
     * — when a term ID was also posted — it must be the same term the slug
     * resolves to. That last check is what stops a posted ID/slug pair that
     * don't actually match (a stale payload, a client bug, or a forged
     * request) from silently being accepted as whichever half happens to
     * validate.
     *
     * @param  array<string, mixed>  $payload
     */
    private static function taxonomyFromArray(array $payload): self
    {
        $taxonomy = sanitize_key((string) ($payload['archiveTaxonomy'] ?? ''));
        $termSlug = sanitize_title((string) ($payload['archiveTerm'] ?? ''));
        $postedTermId = (int) ($payload['queriedObjectId'] ?? 0);

        if ($taxonomy === '' || $termSlug === '' || ! taxonomy_exists($taxonomy)) {
            return self::invalid();
        }

        $term = get_term_by('slug', $termSlug, $taxonomy);

        if (! $term instanceof \WP_Term) {
            return self::invalid();
        }

        if ($postedTermId > 0 && $postedTermId !== (int) $term->term_id) {
            return self::invalid();
        }

        return self::taxonomy($taxonomy, $term->slug, (int) $term->term_id);
    }

    public function isTaxonomy(): bool
    {
        return $this->type === self::TYPE_TAXONOMY;
    }

    public function isInvalid(): bool
    {
        return $this->type === self::TYPE_INVALID;
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

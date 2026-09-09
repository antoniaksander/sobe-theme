<?php

namespace App\WooCommerce\CatalogFilter;

/**
 * The single normalization entry point for turning raw request parameters —
 * from $_GET on a direct archive load, or from a decoded `filter_state` JSON
 * payload posted by AJAX/load-more — into a validated FilterState.
 *
 * Every other place that used to parse filter input independently
 * (FilterHandler::buildTaxQuery()/buildMetaQuery(),
 * CatalogFilters::parseActiveFilters(), the woocommerce_product_query
 * archive-intersection hook) should call fromArray() instead, so a URL
 * always means the same thing regardless of which code path reads it.
 *
 * Backward compatibility: both '+'-delimited (legacy Sobe) and
 * ','-delimited (WooCommerce-canonical, and this parser's own preferred
 * output — see FilterUrlCodec) multi-value encodings are accepted as input.
 * Space-delimited is also accepted since some legacy call sites already
 * split on `[+\s]+`.
 */
final class FilterStateParser
{
    /** Hard cap on terms accepted per taxonomy clause — abuse/DoS guard, not a product limit. */
    private const MAX_TERMS_PER_TAXONOMY = 50;

    private const ALLOWED_PRICE_TYPES = [
        FilterState::PRICE_TYPE_ALL,
        FilterState::PRICE_TYPE_ON_SALE,
        FilterState::PRICE_TYPE_FULL_PRICE,
    ];

    /**
     * Generically supported stock states. Deliberately narrower than every
     * WC_Product stock status (e.g. no 'onbackorder' yet) to match the
     * behavior sobe_catalog_stock_status_list() already shipped — widen here
     * (one place) if a future issue asks for it.
     */
    private const ALLOWED_STOCK_STATUSES = ['instock', 'outofstock'];

    /**
     * WooCommerce's own catalog ordering keys (WC_Query::get_catalog_ordering_args()
     * recognizes these verbatim; anything else it silently falls back on
     * default sorting for, which is exactly why unvalidated orderby is safe
     * but should still be normalized rather than passed through blind).
     */
    private const ALLOWED_ORDERBY = ['menu_order', 'popularity', 'rating', 'date', 'price', 'price-desc', 'title'];

    /**
     * @param  array<string, mixed>  $params  Either $_GET (direct GET / load-more query string)
     *                                        or a decoded `filter_state` JSON object (AJAX / load-more POST).
     */
    public static function fromArray(array $params): FilterState
    {
        $brandTaxonomy = self::brandTaxonomy();

        $categorySlugs = self::termSlugsForKeys($params, ['product_cat', 'filter_product_cat']);
        $tagSlugs = self::termSlugsForKeys($params, ['product_tag', 'filter_product_tag']);
        $brandSlugs = self::termSlugsForKeys($params, [
            'brand', 'filter_brand', 'product_brand', 'filter_product_brand',
            sanitize_key($brandTaxonomy), 'filter_'.sanitize_key($brandTaxonomy),
            'sobe_brands', 'filter_sobe_brands',
        ]);

        $attributes = self::parseAttributes($params, $brandTaxonomy);

        [$minPrice, $maxPrice] = self::parsePriceRange($params);

        return new FilterState(
            categorySlugs: $categorySlugs,
            tagSlugs: $tagSlugs,
            brandSlugs: $brandSlugs,
            attributes: $attributes,
            minPrice: $minPrice,
            maxPrice: $maxPrice,
            priceType: self::parsePriceType($params),
            stockStatuses: self::parseStockStatuses($params),
            orderby: self::parseOrderby($params),
            order: self::parseOrder($params),
            paged: self::parsePaged($params),
            search: self::parseSearch($params),
        );
    }

    /**
     * The taxonomy Sobe treats as "brand" right now — resolved fresh on every
     * call (not cached) so it stays correct across the product_brand
     * registration-ownership handoff (see the sobe/product_brand/register
     * guard): whichever taxonomy owns the name at request time is what gets
     * filtered against.
     */
    public static function brandTaxonomy(): string
    {
        $taxonomy = function_exists('App\sobe_product_brand_taxonomy')
            ? \App\sobe_product_brand_taxonomy()
            : (string) apply_filters('sobe/catalog_filters/brand_taxonomy', 'product_brand');

        return is_string($taxonomy) && $taxonomy !== '' ? $taxonomy : 'product_brand';
    }

    /**
     * @param  string[]  $keys
     * @return string[]
     */
    private static function termSlugsForKeys(array $params, array $keys): array
    {
        $slugs = [];

        foreach ($keys as $key) {
            if (! array_key_exists($key, $params)) {
                continue;
            }

            $slugs = array_merge($slugs, self::splitSlugList($params[$key]));
        }

        return self::dedupeAndCap($slugs);
    }

    /**
     * Rewrites legacy '+'/space-delimited `filter_{attribute}` GET values into
     * canonical comma-delimited form, for registered attributes only.
     *
     * WooCommerce's own native attribute parsing (WC_Query::
     * get_layered_nav_chosen_attributes(), and the product-attributes-lookup
     * -table filterer that replaces it when that WooCommerce feature is
     * active) both split strictly on comma — `explode(',', ...)` — never on
     * '+' or space. FilterStateParser::fromArray() itself accepts either
     * encoding, but the native main query (QueryTransformer::
     * applyToMainQuery()) deliberately leaves attribute filtering entirely to
     * WooCommerce, so a legacy '+'-joined attribute URL (e.g.
     * ?filter_size=42+43) was being handed to WooCommerce's own parser
     * unchanged, which read it as one bogus term ("42+43"/"42 43") that
     * matches nothing — direct GET silently diverged from AJAX for that one
     * case despite this parser itself accepting the URL correctly.
     *
     * Call this once, early (before pre_get_posts / WC_Query::product_query()
     * runs), so whichever internal WooCommerce attribute-filtering mechanism
     * is active sees an already-clean comma-joined value it already
     * understands — this fixes the input WooCommerce reads rather than
     * trying to duplicate or override whichever of WooCommerce's two
     * attribute-filtering code paths happens to be active, which would have
     * to be kept in sync with WooCommerce internals across versions.
     *
     * A value with no '+' or whitespace (a single term, or already
     * comma-joined) is left completely untouched.
     *
     * @param  array<string, mixed>  $params  Typically $_GET.
     * @return array<string, mixed>
     */
    public static function normalizeLegacyAttributeEncoding(array $params): array
    {
        if (! function_exists('wc_get_attribute_taxonomies')) {
            return $params;
        }

        foreach (wc_get_attribute_taxonomies() as $attr) {
            $attrName = sanitize_key((string) $attr->attribute_name);
            $key = "filter_{$attrName}";

            if ($attrName === '' || ! isset($params[$key]) || ! is_string($params[$key])) {
                continue;
            }

            if (! preg_match('/[+\s]/', $params[$key])) {
                continue;
            }

            $params[$key] = implode(',', self::splitSlugList($params[$key]));
        }

        return $params;
    }

    /**
     * Split on '+', ',' or whitespace so legacy '+'-joined values, the new
     * canonical ','-joined values, and a plain array from decoded JSON all
     * normalize identically. sanitize_title() is deliberately applied here
     * (not sanitize_key()) — term slugs may contain characters sanitize_key()
     * would strip.
     */
    private static function splitSlugList(mixed $value): array
    {
        if (is_array($value)) {
            $items = $value;
        } else {
            $items = preg_split('/[+,\s]+/', (string) $value) ?: [];
        }

        return array_values(array_filter(array_map(
            static fn ($item): string => sanitize_title((string) $item),
            $items
        )));
    }

    /**
     * @return string[]
     */
    private static function dedupeAndCap(array $slugs): array
    {
        $slugs = array_values(array_unique(array_filter($slugs)));

        return array_slice($slugs, 0, self::MAX_TERMS_PER_TAXONOMY);
    }

    /**
     * Registered product attributes only (pa_* taxonomies that actually
     * exist) — allowlisted against wc_get_attribute_taxonomies(), never an
     * arbitrary caller-supplied taxonomy name. AND/OR comes from
     * `query_type_{attribute}`, WooCommerce's own native param name for this
     * (reused rather than inventing a Sobe-specific equivalent, so a URL
     * that already works with WooCommerce's layered nav widget means the
     * same thing here).
     *
     * The default when query_type_{attribute} is absent is 'AND', not 'OR'
     * — confirmed by running the real WC_Query::get_layered_nav_chosen_
     * attributes() against a real WooCommerce install: it defaults to
     * apply_filters('woocommerce_layered_nav_default_query_type', 'and'),
     * not 'or'. An earlier version of this parser defaulted to 'or', which
     * is exactly backwards from WooCommerce's own behavior — a multi-value
     * attribute URL with no explicit query_type meant "match every
     * selected term" on a direct GET (native WooCommerce) but "match any
     * selected term" via this parser (AJAX/load-more), a real divergence
     * only a live comparison against WordPress+WooCommerce caught. Calling
     * the same filter here (rather than hardcoding 'and') keeps the two in
     * sync if a store ever customizes the global default.
     *
     * @return array<string, array{terms: string[], operator: 'AND'|'OR'}>
     */
    private static function parseAttributes(array $params, string $brandTaxonomy): array
    {
        if (! function_exists('wc_get_attribute_taxonomies')) {
            return [];
        }

        $reserved = ['product_cat', 'product_tag', sanitize_key($brandTaxonomy), 'brand', 'sobe_brands'];
        $attributes = [];
        $defaultQueryType = strtolower((string) apply_filters('woocommerce_layered_nav_default_query_type', 'and'));

        foreach (wc_get_attribute_taxonomies() as $attr) {
            $attrName = sanitize_key((string) $attr->attribute_name);
            if ($attrName === '' || in_array($attrName, $reserved, true)) {
                continue;
            }

            $taxonomy = function_exists('wc_attribute_taxonomy_name')
                ? wc_attribute_taxonomy_name($attrName)
                : 'pa_'.$attrName;

            if (! taxonomy_exists($taxonomy)) {
                continue;
            }

            $terms = self::termSlugsForKeys($params, [$taxonomy, "filter_{$attrName}", $attrName]);
            if ($terms === []) {
                continue;
            }

            $rawQueryType = $params["query_type_{$attrName}"] ?? null;
            $queryType = in_array($rawQueryType, ['and', 'or'], true)
                ? $rawQueryType
                : $defaultQueryType;
            $operator = $queryType === 'or' ? FilterState::OPERATOR_OR : FilterState::OPERATOR_AND;

            $attributes[$taxonomy] = ['terms' => $terms, 'operator' => $operator];
        }

        return $attributes;
    }

    /**
     * @return array{0: ?float, 1: ?float}
     */
    private static function parsePriceRange(array $params): array
    {
        $min = self::parseNullableFloat($params['min_price'] ?? null);
        $max = self::parseNullableFloat($params['max_price'] ?? null);

        if ($min !== null && $min < 0) {
            $min = 0.0;
        }
        if ($max !== null && $max < 0) {
            $max = 0.0;
        }

        // An inverted range (min > max) is malformed input — fail closed by
        // swapping rather than silently matching everything or nothing.
        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min];
        }

        return [$min, $max];
    }

    private static function parseNullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private static function parsePriceType(array $params): string
    {
        $type = sanitize_key((string) ($params['price_type'] ?? FilterState::PRICE_TYPE_ALL));

        return in_array($type, self::ALLOWED_PRICE_TYPES, true) ? $type : FilterState::PRICE_TYPE_ALL;
    }

    /**
     * @return string[]
     */
    private static function parseStockStatuses(array $params): array
    {
        $raw = $params['stock_status'] ?? $params['filter_stock_status'] ?? [];
        $items = is_array($raw) ? $raw : (preg_split('/[+,\s]+/', (string) $raw) ?: []);

        $statuses = array_map(static fn ($item): string => sanitize_key((string) $item), $items);

        return array_values(array_unique(array_intersect($statuses, self::ALLOWED_STOCK_STATUSES)));
    }

    private static function parseOrderby(array $params): string
    {
        $orderby = sanitize_key((string) ($params['orderby'] ?? 'menu_order'));

        return in_array($orderby, self::ALLOWED_ORDERBY, true) ? $orderby : 'menu_order';
    }

    private static function parseOrder(array $params): string
    {
        $order = strtoupper((string) ($params['order'] ?? 'ASC'));

        return $order === 'DESC' ? 'DESC' : 'ASC';
    }

    private static function parsePaged(array $params): int
    {
        $paged = (int) ($params['paged'] ?? 1);

        return $paged > 0 ? $paged : 1;
    }

    private static function parseSearch(array $params): ?string
    {
        $search = (string) ($params['s'] ?? $params['search'] ?? '');
        $search = sanitize_text_field($search);

        return $search !== '' ? $search : null;
    }
}

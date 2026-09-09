<?php

namespace App\WooCommerce\CatalogFilter;

/**
 * Turns a normalized FilterState + CatalogContext into WooCommerce-aware
 * query constraints, and applies them through two different integration
 * points depending on which query is being built:
 *
 * - applyToMainQuery(): the native WooCommerce main query, already fully
 *   baselined by WC_Query::product_query() (catalog visibility, global
 *   hide-out-of-stock, native pa_* layered nav, and — because it reads
 *   $_GET directly — native min/max price). This method only ADDS what
 *   WooCommerce doesn't already do natively: category/tag/brand selection,
 *   price_type (on_sale/full_price), and the generic stock-state filter.
 *   It deliberately never touches tax_query for pa_* attributes or price —
 *   duplicating either would risk double-filtering or fighting WooCommerce's
 *   own clauses.
 *
 * - buildStandaloneQueryArgs(): a from-scratch WP_Query (AJAX filter
 *   requests, load-more) that does *not* go through WC_Query::product_query()
 *   at all, so it gets none of WooCommerce's baseline for free. This method
 *   replicates that baseline explicitly: catalog-visibility exclusion,
 *   global hide-out-of-stock, every taxonomy (including pa_* attributes,
 *   since there's no native layered nav running for a standalone query),
 *   price via WooCommerce's own price_filter_post_clauses(), and price_type
 *   via wc_get_product_ids_on_sale().
 *
 * Both paths call the same private tax/meta-query builders, so the two
 * integration points can't drift into different interpretations of the same
 * FilterState.
 */
final class QueryTransformer
{
    private static bool $priceFilterHookRegistered = false;

    // ── Native main query (woocommerce_product_query) ──────────────────────

    public static function applyToMainQuery(\WP_Query $query, FilterState $state, CatalogContext $context): void
    {
        $brandTaxonomy = FilterStateParser::brandTaxonomy();

        $taxQuery = is_array($query->get('tax_query')) ? $query->get('tax_query') : [];
        $taxQuery = self::mergeTaxQuery($taxQuery, self::selectionTaxQueryClauses($state, $brandTaxonomy));
        $taxQuery = self::mergeTaxQuery($taxQuery, [self::archiveIntersectionClause($state, $context, $brandTaxonomy)]);

        if ($taxQuery !== []) {
            $query->set('tax_query', $taxQuery);
        }

        if ($state->stockStatuses !== []) {
            $metaQuery = is_array($query->get('meta_query')) ? $query->get('meta_query') : [];
            $query->set('meta_query', self::mergeMetaQuery($metaQuery, [self::stockStatusClause($state->stockStatuses)]));
        }

        self::applyToMainQueryPriceType($query, $state->priceType);
    }

    // ── Standalone query (FilterHandler AJAX / load-more) ──────────────────

    /**
     * @param  array<string, mixed>  $baseArgs  Caller-supplied args (post_type, posts_per_page, ...)
     *                                          that this method extends rather than replaces.
     * @return array<string, mixed>
     */
    public static function buildStandaloneQueryArgs(FilterState $state, CatalogContext $context, array $baseArgs = []): array
    {
        // A context that claimed to be a taxonomy archive but failed
        // validation (see CatalogContext::taxonomyFromArray()) must never
        // fall through to an unscoped query — that would widen a request
        // that was supposed to be narrowed to one archive term into the
        // whole catalog. Fail closed to zero results instead; a caller that
        // wants a distinct error response for this case can check
        // $context->isInvalid() itself before calling this.
        if ($context->isInvalid()) {
            return array_merge($baseArgs, [
                'post_type' => 'product',
                'post__in' => [0],
            ]);
        }

        $brandTaxonomy = FilterStateParser::brandTaxonomy();

        $args = array_merge([
            'post_type' => 'product',
            'post_status' => 'publish',
            'ignore_sticky_posts' => true,
        ], $baseArgs);

        $taxQuery = is_array($args['tax_query'] ?? null) ? $args['tax_query'] : [];
        $taxQuery = self::mergeTaxQuery($taxQuery, [self::visibilityClause($context)]);
        $taxQuery = self::mergeTaxQuery($taxQuery, self::selectionTaxQueryClauses($state, $brandTaxonomy));
        $taxQuery = self::mergeTaxQuery($taxQuery, self::attributeTaxQueryClauses($state));
        $taxQuery = self::mergeTaxQuery($taxQuery, [self::archiveIntersectionClause($state, $context, $brandTaxonomy)]);

        if ($taxQuery !== []) {
            $args['tax_query'] = $taxQuery;
        }

        $metaQuery = is_array($args['meta_query'] ?? null) ? $args['meta_query'] : [];
        if ($state->stockStatuses !== []) {
            $metaQuery = self::mergeMetaQuery($metaQuery, [self::stockStatusClause($state->stockStatuses)]);
        }
        if ($metaQuery !== []) {
            $args['meta_query'] = $metaQuery;
        }

        if ($context->type === CatalogContext::TYPE_SEARCH && $context->search !== null && $context->search !== '') {
            $args['s'] = $context->search;
        } elseif ($state->search !== null && $state->search !== '') {
            $args['s'] = $state->search;
        }

        $args = self::withPriceFilterMarker($args, $state->minPrice, $state->maxPrice);
        $args = self::applyPriceTypeToArgs($args, $state->priceType);

        // Only default paged/orderby/order from $state when the caller
        // didn't already specify them in $baseArgs — this method's own
        // docblock says it "extends rather than replaces" $baseArgs, but
        // unconditionally overwriting these three broke that: a caller
        // (the plain load-more handler) that parses FilterState from a
        // params array with no 'paged' key at all (because pagination
        // arrives as a separate $_POST['page'], not part of filter state)
        // got $state->paged's default of 1 stomped back in over the real
        // requested page it had explicitly passed via $baseArgs — every
        // "load more" request silently re-served page 1.
        if (! array_key_exists('paged', $baseArgs)) {
            $args['paged'] = $state->paged;
        }
        if (! array_key_exists('orderby', $baseArgs)) {
            $args['orderby'] = $state->orderby;
        }
        if (! array_key_exists('order', $baseArgs)) {
            $args['order'] = $state->order;
        }

        return $args;
    }

    /**
     * Runs $factory with $_GET['min_price']/['max_price'] temporarily set so
     * WooCommerce's own WC_Query::price_filter_post_clauses() — which reads
     * $_GET directly, not query vars — applies its normal
     * wc_product_meta_lookup-based range check (correct for variable
     * products and tax-inclusive/exclusive display) to any WP_Query built
     * inside $factory that carries the sobe_catalog_price_filter marker
     * (see withPriceFilterMarker()). $_GET is restored immediately after,
     * synchronously, so nothing else on the request sees the mutation.
     *
     * $factory may return anything (a single WP_Query, or a tuple including
     * one, e.g. FilterHandler::process() also computing term counts inside
     * this same scope so facet counts see the identical effective price
     * constraint as the visible query) — whatever it returns is passed
     * through unchanged.
     *
     * This is the standalone-query equivalent of what already happens for
     * free on a direct GET, where $_GET naturally carries these values and
     * WooCommerce's own main-query price filtering picks them up without any
     * Sobe code at all.
     */
    public static function withPriceFilterQuery(?float $minPrice, ?float $maxPrice, callable $factory): mixed
    {
        if ($minPrice === null && $maxPrice === null) {
            return $factory();
        }

        self::registerPriceFilterHook();

        $overrides = [];
        if ($minPrice !== null) {
            $overrides['min_price'] = (string) $minPrice;
        }
        if ($maxPrice !== null) {
            $overrides['max_price'] = (string) $maxPrice;
        }

        return self::withScopedGetParams($overrides, $factory);
    }

    /**
     * Same need as withPriceFilterQuery(), for a different WooCommerce
     * method: WC_Query::get_catalog_ordering_args() also reads $_GET
     * directly (['orderby']) rather than accepting a parameter, so a
     * standalone query (which received its orderby via POST/JSON, not a
     * real query string) has to feed it through $_GET too. Scoped and
     * restored via the same helper as price filtering, rather than the
     * previous unscoped `$_GET['orderby'] = ...` with no restore, which
     * could leak into anything else reading $_GET later in the same
     * request (facet counts, price range, pagination HTML all run after
     * this, before the AJAX response is sent).
     */
    public static function withOrderbyScope(string $orderby, callable $callback): mixed
    {
        if ($orderby === '') {
            return $callback();
        }

        return self::withScopedGetParams(['orderby' => $orderby], $callback);
    }

    /**
     * Runs $callback with the given $_GET keys temporarily overridden,
     * restoring each key's original value — or removing it, if it wasn't
     * set before — synchronously in a finally block, so a thrown exception
     * can't leave the mutation in place either.
     *
     * @param  array<string, string>  $overrides
     */
    private static function withScopedGetParams(array $overrides, callable $callback): mixed
    {
        $originals = [];
        foreach ($overrides as $key => $value) {
            $originals[$key] = ['had' => array_key_exists($key, $_GET), 'value' => $_GET[$key] ?? null];
            $_GET[$key] = $value;
        }

        try {
            return $callback();
        } finally {
            foreach ($originals as $key => $original) {
                if ($original['had']) {
                    $_GET[$key] = $original['value'];
                } else {
                    unset($_GET[$key]);
                }
            }
        }
    }

    private static function registerPriceFilterHook(): void
    {
        if (self::$priceFilterHookRegistered) {
            return;
        }
        self::$priceFilterHookRegistered = true;

        // Opt this one marked query into WooCommerce's own price-clause
        // filtering, which price_filter_post_clauses() otherwise restricts
        // to $wp_query->is_main_query() — see WC_Query::price_filter_post_clauses().
        add_filter('woocommerce_enable_post_clause_filtering', function ($enable, $wp_query) {
            if ($wp_query instanceof \WP_Query && $wp_query->get('sobe_catalog_price_filter')) {
                return true;
            }

            return $enable;
        }, 10, 2);

        add_filter('posts_clauses', function (array $clauses, \WP_Query $wp_query) {
            if (! $wp_query->get('sobe_catalog_price_filter')) {
                return $clauses;
            }

            if (! function_exists('WC') || ! WC()->query instanceof \WC_Query) {
                return $clauses;
            }

            return WC()->query->price_filter_post_clauses($clauses, $wp_query);
        }, 20, 2);
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private static function withPriceFilterMarker(array $args, ?float $minPrice, ?float $maxPrice): array
    {
        if ($minPrice === null && $maxPrice === null) {
            return $args;
        }

        self::registerPriceFilterHook();
        $args['sobe_catalog_price_filter'] = true;

        return $args;
    }

    // ── price_type (on_sale / full_price) ───────────────────────────────────

    private static function applyToMainQueryPriceType(\WP_Query $query, string $priceType): void
    {
        if ($priceType === FilterState::PRICE_TYPE_ALL || ! function_exists('wc_get_product_ids_on_sale')) {
            return;
        }

        $onSaleIds = array_map('intval', wc_get_product_ids_on_sale());

        if ($priceType === FilterState::PRICE_TYPE_ON_SALE) {
            $existing = array_map('intval', (array) $query->get('post__in'));
            $ids = $existing === [] ? $onSaleIds : array_values(array_intersect($existing, $onSaleIds));
            // No matches must mean "no results", not "no constraint" — 0 is
            // never a real product ID, so post__in=>[0] fails closed.
            $query->set('post__in', $ids === [] ? [0] : $ids);

            return;
        }

        $existingNotIn = array_map('intval', (array) $query->get('post__not_in'));
        $query->set('post__not_in', array_values(array_unique(array_merge($existingNotIn, $onSaleIds))));
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private static function applyPriceTypeToArgs(array $args, string $priceType): array
    {
        if ($priceType === FilterState::PRICE_TYPE_ALL || ! function_exists('wc_get_product_ids_on_sale')) {
            return $args;
        }

        $onSaleIds = array_map('intval', wc_get_product_ids_on_sale());

        if ($priceType === FilterState::PRICE_TYPE_ON_SALE) {
            $existing = array_map('intval', (array) ($args['post__in'] ?? []));
            $ids = $existing === [] ? $onSaleIds : array_values(array_intersect($existing, $onSaleIds));
            $args['post__in'] = $ids === [] ? [0] : $ids;

            return $args;
        }

        $existingNotIn = array_map('intval', (array) ($args['post__not_in'] ?? []));
        $args['post__not_in'] = array_values(array_unique(array_merge($existingNotIn, $onSaleIds)));

        return $args;
    }

    // ── tax_query / meta_query fragment builders ────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function selectionTaxQueryClauses(FilterState $state, string $brandTaxonomy): array
    {
        $clauses = [];

        if ($state->categorySlugs !== []) {
            $clauses[] = ['taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => $state->categorySlugs, 'operator' => 'IN'];
        }
        if ($state->tagSlugs !== []) {
            $clauses[] = ['taxonomy' => 'product_tag', 'field' => 'slug', 'terms' => $state->tagSlugs, 'operator' => 'IN'];
        }
        if ($state->brandSlugs !== [] && taxonomy_exists($brandTaxonomy)) {
            $clauses[] = ['taxonomy' => $brandTaxonomy, 'field' => 'slug', 'terms' => $state->brandSlugs, 'operator' => 'IN'];
        }

        return $clauses;
    }

    /**
     * Only used on the standalone path — the native main query gets
     * attribute filtering for free from WC_Query's own layered nav.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function attributeTaxQueryClauses(FilterState $state): array
    {
        $clauses = [];

        foreach ($state->attributes as $taxonomy => $selection) {
            if (! taxonomy_exists($taxonomy) || $selection['terms'] === []) {
                continue;
            }

            $clauses[] = [
                'taxonomy' => $taxonomy,
                'field' => 'slug',
                'terms' => $selection['terms'],
                'operator' => self::taxQueryOperator($selection['operator']),
            ];
        }

        return $clauses;
    }

    /**
     * FilterState::OPERATOR_OR/OPERATOR_AND ('OR'/'AND') describe the
     * user-facing semantic, not a literal WP_Tax_Query operator value —
     * WP_Tax_Query has no 'OR' operator at all (only IN, NOT IN, AND,
     * EXISTS, NOT EXISTS; an unrecognized operator string is silently
     * ignored, which is exactly how this shipped broken: passing 'OR'
     * straight through produced a syntactically valid-looking tax_query
     * clause that WordPress accepted and then applied no constraint from
     * at all, real-environment-only findable). 'OR' means "matches any of
     * these terms", which is WP_Tax_Query's 'IN'; 'AND' is already a
     * literal WP_Tax_Query operator and passes through unchanged.
     */
    private static function taxQueryOperator(string $filterStateOperator): string
    {
        return $filterStateOperator === FilterState::OPERATOR_AND ? 'AND' : 'IN';
    }

    /**
     * A filter for the SAME taxonomy as the current archive term must
     * intersect with it, never replace it — this is the one place
     * "narrow, never widen or drop" is enforced structurally: the archive
     * term is always re-added as its own AND clause, regardless of what the
     * user selected for that taxonomy.
     */
    private static function archiveIntersectionClause(FilterState $state, CatalogContext $context, string $brandTaxonomy): ?array
    {
        if (! $context->isTaxonomy() || $context->taxonomy === null || $context->termSlug === null) {
            return null;
        }

        if (! taxonomy_exists($context->taxonomy)) {
            return null;
        }

        return [
            'taxonomy' => $context->taxonomy,
            'field' => 'slug',
            'terms' => [$context->termSlug],
            'operator' => 'IN',
        ];
    }

    /**
     * WooCommerce's own visibility exclusion (WC_Query::get_tax_query()),
     * replicated for a standalone query that doesn't go through
     * WC_Query::product_query(). Uses the same public
     * wc_get_product_visibility_term_ids() lookup WooCommerce itself uses,
     * not a hand-rolled term-ID guess.
     */
    private static function visibilityClause(CatalogContext $context): ?array
    {
        if (! function_exists('wc_get_product_visibility_term_ids')) {
            return null;
        }

        $terms = wc_get_product_visibility_term_ids();
        $excludeKey = $context->type === CatalogContext::TYPE_SEARCH ? 'exclude-from-search' : 'exclude-from-catalog';
        $excluded = [];

        if (! empty($terms[$excludeKey])) {
            $excluded[] = $terms[$excludeKey];
        }

        if (get_option('woocommerce_hide_out_of_stock_items') === 'yes' && ! empty($terms['outofstock'])) {
            $excluded[] = $terms['outofstock'];
        }

        if ($excluded === []) {
            return null;
        }

        return [
            'taxonomy' => 'product_visibility',
            'field' => 'term_taxonomy_id',
            'terms' => $excluded,
            'operator' => 'NOT IN',
        ];
    }

    /**
     * _stock_status is a flat, WooCommerce-maintained enum meta (kept in
     * sync on the parent for variable products by WooCommerce core itself),
     * unlike _price/_sale_price — safe to query directly, and this is the
     * same field WooCommerce's own stock-status layered nav filter queries.
     *
     * @param  string[]  $statuses
     */
    private static function stockStatusClause(array $statuses): array
    {
        return ['key' => '_stock_status', 'value' => $statuses, 'compare' => 'IN'];
    }

    /**
     * @param  array<int|string, mixed>  $existing
     * @param  array<int, array<string, mixed>|null>  $additions
     * @return array<int|string, mixed>
     */
    private static function mergeTaxQuery(array $existing, array $additions): array
    {
        $clauses = [];
        foreach ($existing as $key => $item) {
            if ($key === 'relation' || ! is_array($item)) {
                continue;
            }
            $clauses[] = $item;
        }

        foreach ($additions as $clause) {
            if ($clause !== null) {
                $clauses[] = $clause;
            }
        }

        if ($clauses === []) {
            return [];
        }

        return count($clauses) > 1 ? array_merge(['relation' => 'AND'], $clauses) : $clauses;
    }

    /**
     * @param  array<int|string, mixed>  $existing
     * @param  array<int, array<string, mixed>>  $additions
     * @return array<int|string, mixed>
     */
    private static function mergeMetaQuery(array $existing, array $additions): array
    {
        $clauses = [];
        foreach ($existing as $key => $item) {
            if ($key === 'relation' || ! is_array($item)) {
                continue;
            }
            $clauses[] = $item;
        }

        foreach ($additions as $clause) {
            $clauses[] = $clause;
        }

        if ($clauses === []) {
            return [];
        }

        return count($clauses) > 1 ? array_merge(['relation' => 'AND'], $clauses) : $clauses;
    }
}

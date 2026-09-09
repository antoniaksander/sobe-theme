<?php

namespace App\WooCommerce;

use App\WooCommerce\CatalogFilter\CatalogContext;
use App\WooCommerce\CatalogFilter\FilterState;
use App\WooCommerce\CatalogFilter\FilterStateParser;
use App\WooCommerce\CatalogFilter\QueryTransformer;
use function App\sobe_get_filtered_term_counts;
use function App\sobe_get_filtered_price_range;
use function App\sobe_catalog_pagination_html;

/**
 * AJAX handler for catalog filtering.
 *
 * Extracted from the closure in woocommerce.php so the core logic is unit-testable
 * without bootstrapping WordPress HTTP: call process() directly with a filter_state array.
 *
 * Query construction itself lives in CatalogFilter\FilterStateParser and
 * CatalogFilter\QueryTransformer — the same normalized pipeline the native
 * main query (woocommerce-filters.php's woocommerce_product_query hook) and
 * load-more use, so a given filter_state/filter_context pair means the same
 * thing regardless of which of the three ever handles it.
 */
class FilterHandler
{
    public function __construct(private readonly string $prefix) {}

    public function register(): void
    {
        $action = "{$this->prefix}_filter_products";
        add_action("wp_ajax_{$action}", [$this, 'handle']);
        add_action("wp_ajax_nopriv_{$action}", [$this, 'handle']);
    }

    public function handle(): void
    {
        try {
            check_ajax_referer("{$this->prefix}_nonce", 'nonce');
            $raw = sanitize_text_field(wp_unslash($_POST['filter_state'] ?? '{}'));
            $state = json_decode($raw, true) ?: [];
            $contextRaw = sanitize_text_field(wp_unslash($_POST['filter_context'] ?? '{}'));
            $context = json_decode($contextRaw, true) ?: [];
            wp_send_json($this->process($state, $context));
        } catch (\Throwable $e) {
            error_log('[sobe FilterHandler] '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());
            wp_send_json_error(
                ['message' => __('Filter request failed. Please refresh the page and try again.', 'sobe')],
                500
            );
        }
    }

    /**
     * Core logic — no HTTP coupling. Pass any filter_state array; returns response data.
     * Safe to call from tests with WordPress loaded but without a real HTTP request.
     */
    public function process(array $state, array $context = []): array
    {
        $state = (array) apply_filters('sobe/catalog_filters/state', $state, $state);
        $perPage = (int) apply_filters('sobe/shop_loop/per_page', (int) get_theme_mod("{$this->prefix}_products_per_page", config('theme.product_catalog.per_page', 12)), [
            'context' => 'catalog_filters',
        ]);

        $filterState = FilterStateParser::fromArray($state);
        $catalogContext = CatalogContext::fromArray($context);
        $paged = $filterState->paged;

        $queryArgs = $this->buildQueryArgs($filterState, $catalogContext, $perPage);
        $queryArgs = (array) apply_filters('sobe/catalog_filters/query_args', $queryArgs, $state);
        $queryArgs = (array) apply_filters('sobe/shop_loop/query_args', $queryArgs, [
            'context' => 'catalog_filters',
            'state' => $state,
        ]);
        $query = QueryTransformer::withPriceFilterQuery(
            $filterState->minPrice,
            $filterState->maxPrice,
            static fn () => new \WP_Query($queryArgs)
        );

        ob_start();
        if ($query->have_posts()) {
            wc_setup_loop([
                'columns' => (int) apply_filters('sobe/shop_loop/columns', (int) get_theme_mod("{$this->prefix}_product_catalog_desktop_columns", config('theme.product_catalog.desktop_columns', 3)), 'desktop'),
            ]);
            while ($query->have_posts()) {
                $query->the_post();
                global $product;
                if ($product instanceof \WC_Product) {
                    do_action('sobe/shop_loop/before_product_card', $product, ['context' => 'catalog_filters', 'state' => $state]);
                }
                wc_get_template_part('content', 'product');
                if ($product instanceof \WC_Product) {
                    do_action('sobe/shop_loop/after_product_card', $product, ['context' => 'catalog_filters', 'state' => $state]);
                }
            }
            wc_reset_loop();
        }
        wp_reset_postdata();
        $html = (string) ob_get_clean();
        $html = (string) apply_filters('sobe/catalog_filters/results_html', $html, $query, $state);

        $GLOBALS['wp_query'] = $query;
        $paginationHtml = sobe_catalog_pagination_html();
        $paginationHtml = (string) apply_filters('sobe/catalog_filters/pagination_html', $paginationHtml, $query, $state);
        $countHtml = $this->generateCountHtml((int) $query->found_posts, $paged, $perPage);

        $response = [
            'html' => $html,
            'pagination_html' => $paginationHtml,
            'count' => (int) $query->found_posts,
            'count_html' => $countHtml,
            'filters' => sobe_get_filtered_term_counts($queryArgs),
            'price_range' => sobe_get_filtered_price_range($queryArgs),
        ];

        return (array) apply_filters('sobe/catalog_filters/response', $response, $query, $state);
    }

    // ── Query builders ────────────────────────────────────────────────────────

    /**
     * Delegates all filter/visibility/stock/price/archive-context translation
     * to QueryTransformer::buildStandaloneQueryArgs() — the same builder
     * load-more uses — so this AJAX path and load-more can't diverge in how
     * a given FilterState + CatalogContext turn into query args. Only
     * per-page/pagination and WooCommerce's catalog-ordering translation
     * stay here, since those are specific to how this handler paginates and
     * were already correct (get_catalog_ordering_args() is itself a native
     * WooCommerce delegation, not something to re-implement).
     */
    private function buildQueryArgs(FilterState $state, CatalogContext $context, int $perPage): array
    {
        $args = QueryTransformer::buildStandaloneQueryArgs($state, $context, [
            'posts_per_page' => $perPage,
        ]);

        if ($state->orderby !== '') {
            $_GET['orderby'] = $state->orderby;
        }
        if (WC()->query) {
            $ordering = WC()->query->get_catalog_ordering_args();
            $args['orderby'] = $ordering['orderby'];
            $args['order'] = $ordering['order'];
            if (! empty($ordering['meta_key'])) {
                $args['meta_key'] = $ordering['meta_key'];
            }
        }

        return $args;
    }

    private function generateCountHtml(int $total, int $paged, int $perPage): string
    {
        $first = ($paged - 1) * $perPage + 1;
        $last = min($total, $paged * $perPage);

        if ($total === 1) {
            $text = __('Showing the single result', 'woocommerce');
        } elseif ($total <= $perPage) {
            /* translators: %s: total results */
            $text = sprintf(
                __('Showing all %s results', 'woocommerce'),
                '<strong>'.$total.'</strong>'
            );
        } else {
            /* translators: 1: first result 2: last result 3: total results */
            $text = sprintf(
                __('Showing %1$s&ndash;%2$s of %3$s results', 'woocommerce'),
                '<strong>'.$first.'</strong>',
                '<strong>'.$last.'</strong>',
                '<strong>'.$total.'</strong>'
            );
        }

        return '<p class="woocommerce-result-count" data-result-count>'.$text.'</p>';
    }
}

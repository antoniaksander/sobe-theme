<?php

namespace App\WooCommerce;

use function App\sobe_get_filtered_term_counts;

/**
 * AJAX handler for catalog filtering.
 *
 * Extracted from the closure in woocommerce.php so the core logic is unit-testable
 * without bootstrapping WordPress HTTP: call process() directly with a filter_state array.
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
            wp_send_json($this->process($state));
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
    public function process(array $state): array
    {
        $perPage = (int) get_theme_mod("{$this->prefix}_products_per_page", 12);
        $paged = max(1, (int) ($state['paged'] ?? 1));

        $queryArgs = $this->buildQueryArgs($state, $perPage, $paged);
        $query = new \WP_Query($queryArgs);

        ob_start();
        if ($query->have_posts()) {
            wc_setup_loop([
                'columns' => (int) get_theme_mod("{$this->prefix}_product_catalog_desktop_columns", 4),
            ]);
            while ($query->have_posts()) {
                $query->the_post();
                wc_get_template_part('content', 'product');
            }
            wc_reset_loop();
        }
        wp_reset_postdata();
        $html = ob_get_clean();

        $GLOBALS['wp_query'] = $query;
        $paginationHtml = view('woocommerce.loop.pagination')->render();
        $countHtml = $this->generateCountHtml((int) $query->found_posts, $paged, $perPage);

        return [
            'html' => $html,
            'pagination_html' => $paginationHtml,
            'count' => (int) $query->found_posts,
            'count_html' => $countHtml,
            'filters' => sobe_get_filtered_term_counts($queryArgs),
        ];
    }

    // ── Query builders ────────────────────────────────────────────────────────

    private function buildQueryArgs(array $state, int $perPage, int $paged): array
    {
        $args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $perPage,
            'paged' => $paged,
        ];

        $taxQuery = $this->buildTaxQuery($state);
        if (! empty($taxQuery)) {
            $args['tax_query'] = count($taxQuery) > 1
                ? array_merge(['relation' => 'AND'], $taxQuery)
                : $taxQuery;
        }

        $metaQuery = $this->buildMetaQuery($state);
        if (! empty($metaQuery)) {
            $args['meta_query'] = $metaQuery;
        }

        $search = sanitize_text_field($state['s'] ?? '');
        if ($search) {
            $args['s'] = $search;
        }

        $orderby = sanitize_key($state['orderby'] ?? '');
        if ($orderby) {
            $_GET['orderby'] = $orderby;
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

    private function buildTaxQuery(array $state): array
    {
        $clauses = [];

        if (! empty($state['product_cat'])) {
            $clauses[] = [
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => sanitize_text_field($state['product_cat']),
            ];
        }

        if (! empty($state['product_tag'])) {
            $clauses[] = [
                'taxonomy' => 'product_tag',
                'field' => 'slug',
                'terms' => sanitize_text_field($state['product_tag']),
            ];
        }

        foreach ($state as $key => $val) {
            if (! str_starts_with($key, 'filter_')) {
                continue;
            }
            $attrName = substr($key, 7);
            $taxonomy = 'pa_'.sanitize_key($attrName);
            $slugs = array_map('sanitize_text_field', (array) $val);
            if (! empty($slugs)) {
                $clauses[] = [
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => $slugs,
                    'operator' => 'IN',
                ];
            }
        }

        $brandKey = $this->prefix.'_brands';
        $brandSlugs = array_map(
            'sanitize_text_field',
            (array) ($state[$brandKey] ?? $state['product_brand'] ?? [])
        );
        if (! empty($brandSlugs)) {
            $clauses[] = [
                'taxonomy' => 'product_brand',
                'field' => 'slug',
                'terms' => $brandSlugs,
                'operator' => 'IN',
            ];
        }

        return $clauses;
    }

    private function buildMetaQuery(array $state): array
    {
        $clauses = [];

        $minPrice = isset($state['min_price']) ? (float) $state['min_price'] : null;
        $maxPrice = isset($state['max_price']) ? (float) $state['max_price'] : null;

        if ($minPrice !== null || $maxPrice !== null) {
            $price = ['key' => '_price', 'type' => 'NUMERIC'];
            if ($minPrice !== null && $maxPrice !== null) {
                $price['value'] = [$minPrice, $maxPrice];
                $price['compare'] = 'BETWEEN';
            } elseif ($minPrice !== null) {
                $price['value'] = $minPrice;
                $price['compare'] = '>=';
            } else {
                $price['value'] = $maxPrice;
                $price['compare'] = '<=';
            }
            $clauses[] = $price;
        }

        $priceType = sanitize_key($state['price_type'] ?? 'all');
        if ($priceType === 'on_sale') {
            $clauses[] = ['key' => '_sale_price', 'value' => '', 'compare' => '!='];
            $clauses[] = ['key' => '_sale_price', 'value' => '0', 'compare' => '>', 'type' => 'NUMERIC'];
        } elseif ($priceType === 'full_price') {
            $clauses[] = [
                'relation' => 'OR',
                ['key' => '_sale_price', 'compare' => 'NOT EXISTS'],
                ['key' => '_sale_price', 'value' => '', 'compare' => '='],
            ];
        }

        return $clauses;
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

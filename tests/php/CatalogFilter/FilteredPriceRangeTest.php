<?php

/**
 * sobe_get_filtered_price_range() end to end: the raw wc_product_meta_lookup
 * MIN/MAX pair is normalized WooCommerce-style (floor min / ceil max) before
 * it becomes the AJAX `price_range` response, so a cheapest product at 17.95
 * yields a slider minimum of 17, never 18.
 */

namespace {
    if (! class_exists('WooCommerce')) {
        class WooCommerce {}
    }

    if (! class_exists('RoxderFprWpQuery')) {
        class RoxderFprWpQuery
        {
            public array $posts = [];

            public function __construct(array $args = [])
            {
                $this->posts = $GLOBALS['fpr_ids'] ?? [];
            }
        }
    }

    if (! class_exists('WP_Query')) {
        class_alias('RoxderFprWpQuery', 'WP_Query');
    }
}

namespace App {

    use Brain\Monkey\Functions;

    beforeEach(function () {
        $GLOBALS['fpr_ids'] = [11, 22, 33];
        $GLOBALS['fpr_row'] = (object) ['min_price' => '17.95', 'max_price' => '199.95'];

        Functions\when('config')->alias(fn ($k, $d = null) => $k === 'theme.prefix' ? 'sobe' : $d);
        Functions\when('add_action')->justReturn(true);
        Functions\when('add_filter')->justReturn(true);
        Functions\when('apply_filters')->alias(fn ($hook, $value = null) => $value);
        Functions\when('sanitize_key')->alias(fn ($v) => strtolower((string) $v));
        Functions\when('sanitize_text_field')->alias(fn ($v) => trim((string) $v));
        Functions\when('wp_reset_postdata')->justReturn(null);
        Functions\when('wp_unslash')->returnArg();

        $wpdb = new class {
            public string $wc_product_meta_lookup = 'wp_wc_product_meta_lookup';

            public function get_row($sql)
            {
                return $GLOBALS['fpr_row'];
            }
        };
        $GLOBALS['wpdb'] = $wpdb;

        require_once dirname(__DIR__, 3).'/app/woocommerce-filters.php';
    });

    afterEach(function () {
        unset($GLOBALS['wpdb'], $GLOBALS['fpr_ids'], $GLOBALS['fpr_row']);
    });

    it('floors 17.95 to 17 and ceils 199.95 to 200 in the price_range response', function () {
        $range = sobe_get_filtered_price_range(['post_type' => 'product']);

        expect($range)->toBe(['min' => 17.0, 'max' => 200.0]);
    });

    it('passes already-integer bounds through unchanged', function () {
        $GLOBALS['fpr_row'] = (object) ['min_price' => '75.00', 'max_price' => '140.00'];

        expect(sobe_get_filtered_price_range(['post_type' => 'product']))
            ->toBe(['min' => 75.0, 'max' => 140.0]);
    });

    it('returns [0, 0] when the filtered set is empty', function () {
        $GLOBALS['fpr_ids'] = [];

        expect(sobe_get_filtered_price_range(['post_type' => 'product']))
            ->toBe(['min' => 0.0, 'max' => 0.0]);
    });

    it('returns [0, 0] when the lookup row has null aggregates', function () {
        $GLOBALS['fpr_row'] = (object) ['min_price' => null, 'max_price' => null];

        expect(sobe_get_filtered_price_range(['post_type' => 'product']))
            ->toBe(['min' => 0.0, 'max' => 0.0]);
    });
}

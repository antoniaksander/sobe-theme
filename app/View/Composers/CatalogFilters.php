<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class CatalogFilters extends Composer
{
    protected static $views = [
        'blocks.catalog-filters',
    ];

    public function with(): array
    {
        global $wpdb;

        $attrs = (array) ($this->data['attributes'] ?? []);

        $brandsTaxonomy = sanitize_key($attrs['brandsTaxonomy'] ?? 'product_brand');
        $showCategories = (bool) ($attrs['showCategories'] ?? true);
        $showBrands = (bool) ($attrs['showBrands'] ?? true);
        $showAttributes = (bool) ($attrs['showAttributes'] ?? true);
        $showPriceRange = (bool) ($attrs['showPriceRange'] ?? true);
        $collapseByDefault = (bool) ($attrs['collapseByDefault'] ?? true);

        $categories = [];
        if ($showCategories) {
            $cats = get_terms([
                'taxonomy' => 'product_cat',
                'hide_empty' => true,
                'parent' => 0,
            ]);
            $categories = is_wp_error($cats) ? [] : $cats;
        }

        $brands = [];
        if ($showBrands && taxonomy_exists($brandsTaxonomy)) {
            $brandTerms = get_terms([
                'taxonomy' => $brandsTaxonomy,
                'hide_empty' => false,
                'orderby' => 'name',
            ]);
            $brands = is_wp_error($brandTerms) ? [] : (array) $brandTerms;

            // Compute accurate published-product counts via a single JOIN query
            if (! empty($brands)) {
                $brandCountRows = $wpdb->get_results($wpdb->prepare(
                    "SELECT t.term_id, COUNT(DISTINCT p.ID) AS cnt
                     FROM {$wpdb->terms} t
                     JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id AND tt.taxonomy = %s
                     JOIN {$wpdb->term_relationships} tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
                     JOIN {$wpdb->posts} p ON p.ID = tr.object_id
                        AND p.post_type = 'product'
                        AND p.post_status = 'publish'
                     GROUP BY t.term_id",
                    $brandsTaxonomy
                ));
                $brandCountMap = array_column($brandCountRows, 'cnt', 'term_id');
                foreach ($brands as $brand) {
                    $brand->count = (int) ($brandCountMap[$brand->term_id] ?? 0);
                }
                $brands = array_values(array_filter($brands, fn ($b) => $b->count > 0));
            }
        }

        $attributeGroups = [];
        if ($showAttributes && function_exists('wc_get_attribute_taxonomies')) {
            $attrCacheKey = 'sobe_catalog_attribute_groups';
            $cached = wp_cache_get($attrCacheKey);
            if ($cached !== false) {
                $attributeGroups = $cached;
            } else {
                foreach (wc_get_attribute_taxonomies() as $attr) {
                    $taxonomy = wc_attribute_taxonomy_name($attr->attribute_name);
                    $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]);
                    if (! is_wp_error($terms) && ! empty($terms)) {
                        $attr->terms = $terms;
                        $attributeGroups[] = $attr;
                    }
                }
                wp_cache_set($attrCacheKey, $attributeGroups, '', HOUR_IN_SECONDS);
            }
        }

        $priceRange = (object) ['min' => 0, 'max' => 1000];
        if ($showPriceRange) {
            $priceCacheKey = 'sobe_price_range';
            $row = wp_cache_get($priceCacheKey);
            if ($row === false) {
                $row = $wpdb->get_row($wpdb->prepare(
                    "SELECT MIN(meta_value+0) AS min_price, MAX(meta_value+0) AS max_price
                     FROM {$wpdb->postmeta}
                     WHERE meta_key = %s
                     AND meta_value != ''
                     AND meta_value IS NOT NULL",
                    '_price'
                ));
                wp_cache_set($priceCacheKey, $row, '', HOUR_IN_SECONDS);
            }
            if ($row) {
                $priceRange = (object) [
                    'min' => (float) ($row->min_price ?? 0),
                    'max' => (float) ($row->max_price ?? 1000),
                ];
            }
        }

        $activeFilters = $this->parseActiveFilters();

        // On a brand archive page, pre-check the current brand and force-open the accordion
        // so the user immediately sees which brand they're browsing and can scroll the list.
        $brandsOpenByDefault = ! $collapseByDefault;
        if ($showBrands && is_tax($brandsTaxonomy)) {
            $currentTerm = get_queried_object();
            if ($currentTerm instanceof \WP_Term && $currentTerm->taxonomy === $brandsTaxonomy) {
                if (empty($activeFilters[$brandsTaxonomy])) {
                    $activeFilters[$brandsTaxonomy] = [$currentTerm->slug];
                }
                $brandsOpenByDefault = true;
            }
        }

        return compact(
            'categories',
            'brands',
            'attributeGroups',
            'priceRange',
            'activeFilters',
            'showCategories',
            'showBrands',
            'showAttributes',
            'showPriceRange',
            'collapseByDefault',
            'brandsTaxonomy',
            'brandsOpenByDefault'
        );
    }

    private function parseActiveFilters(): array
    {
        $active = [];

        $cat = sanitize_text_field($_GET['product_cat'] ?? '');
        if ($cat) {
            $active['product_cat'] = $cat;
        }

        foreach ($_GET as $key => $value) {
            if (strpos($key, 'filter_') !== 0) {
                continue;
            }
            $name = substr($key, 7);
            $slugs = explode('+', urldecode(sanitize_text_field($value)));
            $active[$name] = array_filter($slugs);
        }

        $minPrice = sanitize_text_field($_GET['min_price'] ?? '');
        $maxPrice = sanitize_text_field($_GET['max_price'] ?? '');
        if ($minPrice !== '') {
            $active['min_price'] = $minPrice;
        }
        if ($maxPrice !== '') {
            $active['max_price'] = $maxPrice;
        }

        $priceType = sanitize_key($_GET['price_type'] ?? '');
        if (in_array($priceType, ['on_sale', 'full_price'], true)) {
            $active['price_type'] = $priceType;
        }

        return $active;
    }
}

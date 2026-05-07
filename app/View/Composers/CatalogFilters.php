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
                'hide_empty' => true,
                'orderby' => 'name',
            ]);
            $brands = is_wp_error($brandTerms) ? [] : $brandTerms;
        }

        $attributeGroups = [];
        if ($showAttributes && function_exists('wc_get_attribute_taxonomies')) {
            foreach (wc_get_attribute_taxonomies() as $attr) {
                $taxonomy = wc_attribute_taxonomy_name($attr->attribute_name);
                $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]);
                if (! is_wp_error($terms) && ! empty($terms)) {
                    $attr->terms = $terms;
                    $attributeGroups[] = $attr;
                }
            }
        }

        $priceRange = (object) ['min' => 0, 'max' => 1000];
        if ($showPriceRange) {
            $row = $wpdb->get_row(
                "SELECT MIN(meta_value+0) AS min_price, MAX(meta_value+0) AS max_price
                 FROM {$wpdb->postmeta}
                 WHERE meta_key = '_price'
                 AND meta_value != ''
                 AND meta_value IS NOT NULL"
            );
            if ($row) {
                $priceRange = (object) [
                    'min' => (float) ($row->min_price ?? 0),
                    'max' => (float) ($row->max_price ?? 1000),
                ];
            }
        }

        $activeFilters = $this->parseActiveFilters();

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
            'brandsTaxonomy'
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

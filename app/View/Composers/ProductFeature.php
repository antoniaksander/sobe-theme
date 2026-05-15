<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class ProductFeature extends Composer
{
    protected static $views = [
        'blocks.product-feature',
    ];

    public function with(): array
    {
        $attrs = (array) ($this->data['attributes'] ?? []);
        $productId = (int) apply_filters(
            'sobe/product_feature/product_id',
            $attrs['productId'] ?? 0,
            $attrs
        );

        if (! $productId || ! function_exists('wc_get_product')) {
            return $this->empty();
        }

        $product = wc_get_product($productId);

        if (! $product instanceof \WC_Product) {
            return $this->empty();
        }

        $productName = $product->get_name();
        $productUrl = (string) get_permalink($productId);
        $productPrice = $product->get_price_html();

        $productImage = '';
        $productImageAlt = '';
        $imageId = $product->get_image_id();
        if ($imageId) {
            $productImage = (string) wp_get_attachment_image_url($imageId, 'large');
            $productImageAlt = (string) (get_post_meta($imageId, '_wp_attachment_image_alt', true) ?: $productName);
        }

        $brandTaxonomy = sanitize_key((string) apply_filters(
            'sobe/product_feature/brand_taxonomy',
            'product_brand',
            $product,
            $attrs
        ));
        $productBrand = '';
        $brandTerms = get_the_terms($productId, $brandTaxonomy);
        if ($brandTerms && ! is_wp_error($brandTerms)) {
            $productBrand = $brandTerms[0]->name;
        }

        $data = [
            'product' => $product,
            'productName' => $productName,
            'productPrice' => $productPrice,
            'productImage' => $productImage,
            'productImageAlt' => $productImageAlt,
            'productBrand' => $productBrand,
            'productUrl' => $productUrl,
        ];

        $filtered = apply_filters('sobe/product_feature/data', $data, $product, $attrs);

        return is_array($filtered) ? $filtered : $data;
    }

    private function empty(): array
    {
        return [
            'product' => null,
            'productName' => '',
            'productPrice' => '',
            'productImage' => '',
            'productImageAlt' => '',
            'productBrand' => '',
            'productUrl' => '',
        ];
    }
}

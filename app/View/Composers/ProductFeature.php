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
        $productId = (int) ($attrs['productId'] ?? 0);

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

        // product_brand taxonomy is registered in setup.php
        $productBrand = '';
        $brandTerms = get_the_terms($productId, 'product_brand');
        if ($brandTerms && ! is_wp_error($brandTerms)) {
            $productBrand = $brandTerms[0]->name;
        }

        return [
            'product' => $product,
            'productName' => $productName,
            'productPrice' => $productPrice,
            'productImage' => $productImage,
            'productImageAlt' => $productImageAlt,
            'productBrand' => $productBrand,
            'productUrl' => $productUrl,
        ];
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

<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class ProductCategoriesGrid extends Composer
{
    protected static $views = [
        'blocks.product-categories-grid',
    ];

    public function with(): array
    {
        $attrs = (array) ($this->data['attributes'] ?? []);
        $rawItems = $attrs['items'] ?? [];

        if (! is_array($rawItems) || ! taxonomy_exists('product_cat')) {
            return ['categories' => []];
        }

        $resolved = [];

        foreach ($rawItems as $row) {
            if (! is_array($row)) {
                continue;
            }

            $termId = (int) ($row['termId'] ?? 0);
            $customImageId = (int) ($row['imageId'] ?? 0);

            if (! $termId) {
                continue;
            }

            $term = get_term($termId, 'product_cat');

            if (! $term instanceof \WP_Term || is_wp_error($term)) {
                continue;
            }

            $imageUrl = '';
            $imageAlt = $term->name;

            if ($customImageId) {
                $imageUrl = (string) wp_get_attachment_image_url($customImageId, 'large');
                $alt = get_post_meta($customImageId, '_wp_attachment_image_alt', true);
                $imageAlt = is_string($alt) && $alt !== '' ? $alt : $term->name;
            } else {
                $thumbId = (int) get_term_meta($termId, 'thumbnail_id', true);

                if ($thumbId) {
                    $imageUrl = (string) wp_get_attachment_image_url($thumbId, 'large');
                    $alt = get_post_meta($thumbId, '_wp_attachment_image_alt', true);
                    $imageAlt = is_string($alt) && $alt !== '' ? $alt : $term->name;
                }
            }

            $link = get_term_link($term);

            $resolved[] = [
                'id' => (int) $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'count' => (int) $term->count,
                'link' => is_wp_error($link) ? '' : (string) $link,
                'imageUrl' => $imageUrl,
                'imageAlt' => $imageAlt,
            ];
        }

        return [
            'categories' => $resolved,
        ];
    }
}

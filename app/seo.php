<?php

/**
 * Baseline structured data for non-WooCommerce content.
 *
 * WooCommerce core (WC_Structured_Data) already emits Product, Offer, Brand,
 * and BreadcrumbList JSON-LD on shop / product / category pages. This file
 * intentionally covers ONLY regular posts and pages so the baseline never
 * duplicates WooCommerce's markup.
 *
 * Output is wired in resources/views/layouts/app.blade.php and is skipped
 * automatically when a dedicated SEO plugin is active (same gate as the rest
 * of the baseline meta).
 */

namespace App;

/**
 * True when the current request is a WooCommerce-owned context that already
 * ships its own structured data.
 */
function sobe_seo_is_woocommerce_context(): bool
{
    foreach ([
        'is_woocommerce',
        'is_product',
        'is_product_category',
        'is_product_tag',
        'is_shop',
        'is_cart',
        'is_checkout',
        'is_account_page',
    ] as $conditional) {
        if (function_exists($conditional) && $conditional()) {
            return true;
        }
    }

    return false;
}

/**
 * BreadcrumbList for non-WooCommerce singular content (posts and pages).
 * Returns null when nothing meaningful can be built.
 */
function sobe_seo_breadcrumb_schema(): ?array
{
    if (sobe_seo_is_woocommerce_context() || ! is_singular()) {
        return null;
    }

    $post = get_queried_object();

    if (! $post instanceof \WP_Post) {
        return null;
    }

    $crumbs = [['name' => __('Home', config('theme.textdomain')), 'url' => home_url('/')]];

    foreach (array_reverse(get_post_ancestors($post->ID)) as $ancestorId) {
        $crumbs[] = ['name' => get_the_title($ancestorId), 'url' => (string) get_permalink($ancestorId)];
    }

    $crumbs[] = ['name' => get_the_title($post), 'url' => (string) get_permalink($post)];

    if (count($crumbs) < 2) {
        return null;
    }

    $elements = [];

    foreach (array_values($crumbs) as $index => $crumb) {
        $elements[] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => wp_strip_all_tags((string) $crumb['name']),
            'item' => esc_url_raw((string) $crumb['url']),
        ];
    }

    return [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $elements,
    ];
}

/**
 * Article schema for single blog posts.
 */
function sobe_seo_article_schema(): ?array
{
    if (! is_singular('post')) {
        return null;
    }

    $post = get_queried_object();

    if (! $post instanceof \WP_Post) {
        return null;
    }

    $node = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => wp_strip_all_tags(get_the_title($post)),
        'datePublished' => get_the_date('c', $post),
        'dateModified' => get_the_modified_date('c', $post),
        'mainEntityOfPage' => esc_url_raw((string) get_permalink($post)),
        'author' => [
            '@type' => 'Person',
            'name' => get_the_author_meta('display_name', (int) $post->post_author),
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
        ],
    ];

    if (has_post_thumbnail($post)) {
        $node['image'] = esc_url_raw((string) get_the_post_thumbnail_url($post, 'large'));
    }

    return $node;
}

/**
 * All baseline structured-data nodes for the current non-WooCommerce request.
 * Forks can extend or replace via the `sobe/seo/extra_schema` filter.
 *
 * @return array<int, array<string, mixed>>
 */
function sobe_seo_extra_schema_nodes(): array
{
    $nodes = array_values(array_filter([
        sobe_seo_breadcrumb_schema(),
        sobe_seo_article_schema(),
    ]));

    return (array) apply_filters('sobe/seo/extra_schema', $nodes);
}

<?php

/**
 * Guarded migration: legacy [woocommerce_checkout] shortcode page -> Checkout block.
 *
 * Mirrors app/cart-migration.php. WooCommerce surfaces a manual admin nudge for
 * this; this adds a scriptable, exact-match-guarded version for client
 * onboarding / deploys, exposed as a WP-CLI command:
 *
 *     wp sobe migrate:checkout-page [--dry-run]
 *
 * The migration ONLY runs when the checkout page content is exactly a recognised
 * legacy shortcode form, so a customised checkout page is never clobbered.
 */

namespace App;

/**
 * Recognised legacy checkout-page content forms.
 *
 * @return array<int, string>
 */
function sobe_legacy_checkout_content_forms(): array
{
    return [
        '[woocommerce_checkout]',
        '<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->',
    ];
}

/**
 * Strip all whitespace so the comparison tolerates the newlines WordPress
 * stores around a shortcode block, while still rejecting any real extra content.
 */
function sobe_normalize_checkout_content(string $content): string
{
    return (string) preg_replace('/\s+/', '', $content);
}

/**
 * Whether the given page content is exactly a recognised legacy checkout shortcode.
 * Pure (no WordPress) so it is unit-testable.
 */
function sobe_is_legacy_checkout_content(string $content): bool
{
    $current = sobe_normalize_checkout_content($content);

    foreach (sobe_legacy_checkout_content_forms() as $form) {
        if ($current !== '' && $current === sobe_normalize_checkout_content($form)) {
            return true;
        }
    }

    return false;
}

/**
 * The Checkout block markup to migrate to. Reuses WooCommerce's own canonical
 * block content when available (so it matches a fresh install and never goes
 * stale); falls back to the minimal self-closing block. Filterable.
 */
function sobe_checkout_block_markup(): string
{
    $markup = '<!-- wp:woocommerce/checkout /-->';

    if (class_exists('WC_Install') && method_exists('WC_Install', 'get_checkout_block_content')) {
        try {
            // PHP 8.1+ invokes non-public methods via reflection without setAccessible().
            $content = (string) (new \ReflectionMethod('WC_Install', 'get_checkout_block_content'))->invoke(null);

            if ($content !== '') {
                $markup = $content;
            }
        } catch (\Throwable) {
            // Keep the fallback markup.
        }
    }

    return (string) apply_filters('sobe/checkout_migration/block_markup', $markup);
}

/**
 * Migrate the configured checkout page from the legacy shortcode to the Checkout
 * block, but only when its content is exactly a recognised legacy form.
 *
 * @return array{status: string, reason?: string, page_id?: int}
 */
function sobe_migrate_legacy_checkout_page(bool $dry_run = false): array
{
    if (! function_exists('wc_get_page_id')) {
        return ['status' => 'skipped', 'reason' => 'woocommerce-inactive'];
    }

    $page_id = (int) wc_get_page_id('checkout');
    if ($page_id <= 0) {
        return ['status' => 'skipped', 'reason' => 'no-checkout-page'];
    }

    $page = get_post($page_id);
    if (! $page instanceof \WP_Post) {
        return ['status' => 'skipped', 'reason' => 'no-checkout-page'];
    }

    if (! sobe_is_legacy_checkout_content((string) $page->post_content)) {
        return ['status' => 'skipped', 'reason' => 'not-legacy-or-customised', 'page_id' => $page_id];
    }

    if ($dry_run) {
        return ['status' => 'would-migrate', 'page_id' => $page_id];
    }

    $result = wp_update_post([
        'ID' => $page_id,
        'post_content' => sobe_checkout_block_markup(),
    ], true);

    if (is_wp_error($result)) {
        return ['status' => 'error', 'reason' => $result->get_error_message(), 'page_id' => $page_id];
    }

    do_action('sobe/checkout_migration/migrated', $page_id);

    return ['status' => 'migrated', 'page_id' => $page_id];
}

// ── WP-CLI command ───────────────────────────────────────────────────────────
if (defined('WP_CLI') && \WP_CLI) {
    \WP_CLI::add_command('sobe migrate:checkout-page', function (array $args, array $assoc_args): void {
        $result = sobe_migrate_legacy_checkout_page(isset($assoc_args['dry-run']));
        $page_id = $result['page_id'] ?? 0;

        switch ($result['status']) {
            case 'migrated':
                \WP_CLI::success("Checkout page #{$page_id} migrated to the Checkout block.");
                break;
            case 'would-migrate':
                \WP_CLI::log("[dry-run] Checkout page #{$page_id} would be migrated to the Checkout block.");
                break;
            case 'error':
                \WP_CLI::error("Migration failed: {$result['reason']}.");
                break;
            default:
                \WP_CLI::warning("Skipped: {$result['reason']}.");
                break;
        }
    });
}

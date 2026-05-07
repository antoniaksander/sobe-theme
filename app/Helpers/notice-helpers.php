<?php

namespace App\Helpers;

function sobe_is_side_cart_enabled(): bool
{
    return (bool) \get_theme_mod(\config('theme.prefix').'_enable_side_cart', true);
}

function sobe_get_notices_for_toast(): array
{
    $notices = \wc_get_notices();
    $filtered = [];

    foreach (['success', 'error', 'notice'] as $type) {
        if (empty($notices[$type])) {
            continue;
        }
        foreach ($notices[$type] as $notice) {
            $message = is_string($notice) ? $notice : ($notice['notice'] ?? '');
            $message = \wp_strip_all_tags($message);
            if ($message === '') {
                continue;
            }

            $filtered[] = [
                'id' => 'toast-'.\wp_rand(100000, 999999),
                'type' => $type,
                'message' => $message,
            ];
        }
    }

    \wc_clear_notices();

    return $filtered;
}

function sobe_get_empty_notices_wrapper(): string
{
    return '<div class="woocommerce-notices-wrapper"></div>';
}

<?php

/**

 * Demo layout hooks for the v2 shell.

 */



namespace App;



use function Roots\view;



function sobe_render_layout_pattern(string $type, string $variant): string
{
    if ($variant === 'none') {
        return '';
    }

    $blockName = config('theme.prefix').'/site-'.$type;
    $markup = sprintf('<!-- wp:%s %s /-->', $blockName, wp_json_encode(['variant' => $variant]));

    return do_blocks($markup);
}

add_action('get_header', function (): void {
    if (function_exists('is_checkout') && is_checkout()) {
        echo view('sections.checkout-header')->render();
        return;
    }

    echo sobe_render_layout_pattern('header', get_theme_mod(config('theme.prefix').'_header_layout', 'header-1'));
});

add_action('get_footer', function (): void {
    if (function_exists('is_checkout') && is_checkout()) {
        return;
    }

    echo view('sections.footer')->render();
});

add_action('wp_footer', function (): void {
    echo view('components.side-cart')->render();
    echo '<div class="fixed bottom-6 right-6 z-[9999] flex flex-col gap-3 pointer-events-none" aria-live="polite">';
    echo view('components.toast-container')->render();
    echo '</div>';
    echo view('partials.search-overlay')->render();
}, 5);

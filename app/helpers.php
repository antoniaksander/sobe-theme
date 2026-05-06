<?php

namespace App;

/**
 * Render a layout block pattern (header or footer) by type and Customizer variant.
 *
 * Constructs a block comment markup string and runs it through do_blocks(), which
 * triggers the sobe/site-header or sobe/site-footer render callback. The callback
 * delegates to the corresponding Blade section file.
 *
 * Call with the \App\ prefix from Blade: {!! \App\sobe_render_layout_pattern(...) !!}
 * Blade templates execute in the global namespace, so the prefix is required.
 *
 * @param string $type    'header' or 'footer'
 * @param string $variant Customizer value — e.g. 'header-1', 'header-2', 'layout-2', 'none'
 */
function sobe_render_layout_pattern(string $type, string $variant): string
{
    if ($variant === 'none') {
        return '';
    }

    $block_name = config('theme.prefix') . '/site-' . $type;
    $markup     = sprintf('<!-- wp:%s %s /-->', $block_name, wp_json_encode(['variant' => $variant]));

    return do_blocks($markup);
}

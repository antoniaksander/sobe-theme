<?php

/**
 * Layout helpers.
 */

namespace App;

function sobe_render_layout_pattern(string $type, string $variant): string
{
    if ($variant === 'none') {
        return '';
    }

    $blockName = (string) apply_filters(
        'sobe/layout/block_name',
        "sobe/site-{$type}",
        $type,
        $variant
    );

    if ($blockName === '') {
        return '';
    }

    $markup = sprintf('<!-- wp:%s %s /-->', $blockName, wp_json_encode(['variant' => $variant]));

    return do_blocks($markup);
}

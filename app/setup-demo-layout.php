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

    $blockName = config('theme.prefix').'/site-'.$type;
    $markup = sprintf('<!-- wp:%s %s /-->', $blockName, wp_json_encode(['variant' => $variant]));

    return do_blocks($markup);
}

<?php

/**
 * Generic theme filters.
 */

namespace App;

add_filter('excerpt_length', fn () => config('theme.excerpt_length'), 999);

add_filter('excerpt_more', function () {
    return sprintf(
        ' &hellip; <a href="%s">%s</a>',
        esc_url(get_permalink()),
        __('Continued', config('theme.textdomain'))
    );
});

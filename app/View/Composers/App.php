<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class App extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var array
     */
    protected static $views = [
        '*',
    ];

    /**
     * Retrieve the site name.
     */
    public function siteName(): string
    {
        return get_bloginfo('name', 'display');
    }

    /**
     * Retrieve the light logo URL.
     */
    public function logo(): ?string
    {
        $logoId = get_theme_mod(config('theme.prefix').'_logo');
        if (! $logoId) {
            return null;
        }
        $url = wp_get_attachment_image_url($logoId, 'full');

        return $url ?: null;
    }

    /**
     * Retrieve the dark logo URL.
     */
    public function darkLogo(): ?string
    {
        $logoId = get_theme_mod(config('theme.prefix').'_dark_logo');
        if (! $logoId) {
            return null;
        }
        $url = wp_get_attachment_image_url($logoId, 'full');

        return $url ?: null;
    }

    /**
     * Retrieve the current logo URL based on dark mode.
     */
    public function currentLogo(): ?string
    {
        $logo = $this->logo();
        $darkLogo = $this->darkLogo();

        if (! $logo && ! $darkLogo) {
            return null;
        }

        if ($logo && ! $darkLogo) {
            return $logo;
        }

        if (! $logo && $darkLogo) {
            return $darkLogo;
        }

        return $logo;
    }
}

<?php

namespace App\Providers;

use Roots\Acorn\Sage\SageServiceProvider;

class ThemeServiceProvider extends SageServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        parent::register();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        $this->registerClientViews();
    }

    /**
     * Client view overrides: resources/views/client/ is searched before
     * resources/views/, for Blade view names, the WordPress template
     * hierarchy and WooCommerce templates alike. A fork copies a platform
     * view to the same relative path under client/ and edits the copy, so
     * the platform file stays identical to upstream.
     */
    protected function registerClientViews(): void
    {
        $clientViews = resource_path('views/client');

        if (is_dir($clientViews)) {
            $this->app['view']->getFinder()->prependLocation($clientViews);
        }
    }
}

<?php

namespace Javaabu\Stencil;

use Illuminate\Support\ServiceProvider;

class StencilServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/stencil.php', 'stencil');
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'stencil');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'stencil');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../config/stencil.php' => config_path('stencil.php'),
        ], 'stencil-config');

        $this->publishes([
            __DIR__ . '/../lang' => lang_path('vendor/stencil'),
        ], 'stencil-translations');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/stencil'),
        ], 'stencil-views');
    }
}

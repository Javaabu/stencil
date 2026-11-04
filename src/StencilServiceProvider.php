<?php

namespace Javaabu\Stencil;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Javaabu\Manifest\Concerns\RegistersAssets;
use Javaabu\Manifest\Package;
use Javaabu\Stencil\Http\Controllers\EvaluateController;

class StencilServiceProvider extends ServiceProvider
{
    use RegistersAssets;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/stencil.php', 'stencil');

        $this->app->singleton(StencilManager::class, fn ($app) => new StencilManager($app));
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'stencil');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'stencil');

        $this->registerAssets('stencil', __DIR__ . '/../resources', fn (Package $package) => $package
            ->js('js')
            ->dist('../dist')
            ->style('styles', 'css/stencil.css')
            ->script('stencil', 'js/blade/stencil.ts', depends_on: ['forms::forms', 'stencil::styles'])
            ->npm([
                '@javaabu/forms' => 'github:Javaabu/react-forms#next-gen',
            ], [
                'react' => '^19.0',
                'react-dom' => '^19.0',
            ]));

        $this->registerRouteMacro();

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

    /**
     * Route::stencil(): the route live forms send their state to. Apps choose the
     * URI and the middleware: Route::stencil()->middleware('auth:admin').
     */
    protected function registerRouteMacro(): void
    {
        Router::macro('stencil', function (string $uri = 'stencil/evaluate', ?string $controller = null) {
            /** @var Router $this */
            return $this->post($uri, $controller ?? config('stencil.live.controller', EvaluateController::class))
                ->name(config('stencil.live.route', 'stencil.evaluate'));
        });
    }
}

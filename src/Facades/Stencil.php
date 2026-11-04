<?php

namespace Javaabu\Stencil\Facades;

use Closure;
use Illuminate\Support\Facades\Facade;
use Javaabu\Stencil\Contracts\Renderer;
use Javaabu\Stencil\Form;
use Javaabu\Stencil\StencilManager;

/**
 * @method static Renderer renderer(?string $name = null)
 * @method static mixed render(Form $form, ?string $driver = null)
 * @method static StencilManager extend(string $driver, Closure $callback)
 * @method static StencilManager view(string $type, string $view)
 * @method static string viewFor(string $type)
 * @method static string getDefaultDriver()
 *
 * @see StencilManager
 */
class Stencil extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return StencilManager::class;
    }
}

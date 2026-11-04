<?php

namespace Javaabu\Stencil;

use Illuminate\Support\Manager;
use Javaabu\Stencil\Contracts\Renderer;
use Javaabu\Stencil\Renderers\BladeRenderer;
use Javaabu\Stencil\Renderers\JsonRenderer;

/**
 * The renderer drivers: blade, json (also called inertia), and any added with extend().
 */
class StencilManager extends Manager
{
    /**
     * Blade views for component types, added by packages and apps.
     *
     * @var array<string, string>
     */
    protected array $views = [];

    public function getDefaultDriver(): string
    {
        return config('stencil.views', 'blade');
    }

    public function renderer(?string $name = null): Renderer
    {
        return $this->driver($name);
    }

    /**
     * Render a form with a driver, or the default one.
     */
    public function render(Form $form, ?string $driver = null): mixed
    {
        return $this->renderer($driver)->render($form);
    }

    protected function createBladeDriver(): Renderer
    {
        return $this->container->make(BladeRenderer::class);
    }

    protected function createJsonDriver(): Renderer
    {
        return $this->container->make(JsonRenderer::class);
    }

    protected function createInertiaDriver(): Renderer
    {
        return $this->createJsonDriver();
    }

    /**
     * Render a component type with a Blade view: Stencil::view('rating', 'components.stencil.rating').
     */
    public function view(string $type, string $view): static
    {
        $this->views[$type] = $view;

        return $this;
    }

    public function viewFor(string $type): string
    {
        return $this->views[$type] ?? "stencil::components.{$type}";
    }

    /**
     * @return array<string, string>
     */
    public function views(): array
    {
        return $this->views;
    }
}

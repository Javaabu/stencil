<?php

namespace Javaabu\Stencil\Tests\Feature;

use Javaabu\Stencil\Contracts\Renderer;
use Javaabu\Stencil\Renderers\BladeRenderer;
use Javaabu\Stencil\Testing\RendererTestKit;
use Javaabu\Stencil\Tests\TestCase;

class BladeRendererKitTest extends TestCase
{
    use RendererTestKit;

    protected function renderer(): Renderer
    {
        return app(BladeRenderer::class);
    }
}

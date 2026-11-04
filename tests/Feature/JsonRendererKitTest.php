<?php

namespace Javaabu\Stencil\Tests\Feature;

use Javaabu\Stencil\Contracts\Renderer;
use Javaabu\Stencil\Renderers\JsonRenderer;
use Javaabu\Stencil\Testing\RendererTestKit;
use Javaabu\Stencil\Tests\TestCase;

class JsonRendererKitTest extends TestCase
{
    use RendererTestKit;

    protected function renderer(): Renderer
    {
        return app(JsonRenderer::class);
    }
}

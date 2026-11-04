<?php

namespace Javaabu\Stencil\Tests\Unit;

use Javaabu\Stencil\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ServiceProviderTest extends TestCase
{
    #[Test]
    public function it_merges_the_default_config(): void
    {
        $this->assertEquals('blade', config('stencil.views'));
        $this->assertEquals('stencil.evaluate', config('stencil.live.route'));
    }
}

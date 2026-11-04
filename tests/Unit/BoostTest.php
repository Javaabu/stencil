<?php

namespace Javaabu\Stencil\Tests\Unit;

use Javaabu\Stencil\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class BoostTest extends TestCase
{
    #[Test]
    public function it_ships_the_boost_guideline_and_skill(): void
    {
        $root = dirname(__DIR__, 2) . '/resources/boost';

        $this->assertFileExists("{$root}/guidelines/core.blade.php");

        $skill = file_get_contents("{$root}/skills/javaabu-stencil/SKILL.md");

        $this->assertMatchesRegularExpression('/^---\nname: javaabu-stencil\ndescription: .+\n/', $skill);

        preg_match_all('/\]\((references\/[^)]+)\)/', $skill, $matches);

        foreach ($matches[1] as $reference) {
            $this->assertFileExists("{$root}/skills/javaabu-stencil/{$reference}");
        }

        $this->assertNotEmpty($matches[1]);
    }

    #[Test]
    public function it_compiles_the_guideline(): void
    {
        $html = view()->file(dirname(__DIR__, 2) . '/resources/boost/guidelines/core.blade.php')->render();

        $this->assertStringContainsString('{{ $form }}', $html);
        $this->assertStringContainsString('javaabu-stencil', $html);
    }
}

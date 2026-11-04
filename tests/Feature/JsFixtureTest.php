<?php

namespace Javaabu\Stencil\Tests\Feature;

use Javaabu\Stencil\Tests\Fixtures\Forms\KitchenSinkForm;
use Javaabu\Stencil\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Keeps the schema the React tests render in step with the JSON driver.
 * Run with STENCIL_UPDATE_FIXTURES=1 to rewrite it.
 */
class JsFixtureTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->stencil();
    }

    #[Test]
    public function it_matches_the_schema_the_react_tests_render(): void
    {
        $json = KitchenSinkForm::make()
            ->model(['title' => 'ރާއްޖެ', 'type' => 'person', 'channels' => ['email'], 'size' => 'l', 'subscribe' => true])
            ->action('/kitchen-sink')
            ->toArray();

        $json['live']['token'] = 'test-token';

        $encoded = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        $path = __DIR__ . '/../js/fixtures/kitchen-sink.json';

        if (getenv('STENCIL_UPDATE_FIXTURES')) {
            file_put_contents($path, $encoded);
        }

        $this->assertJsonStringEqualsJsonFile($path, $encoded);
    }
}

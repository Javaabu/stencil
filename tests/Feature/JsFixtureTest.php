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

    #[Test]
    public function it_matches_the_html_the_blade_script_tests_enhance(): void
    {
        $fixtures = [
            'kitchen-sink.html' => ['title' => 'ރާއްޖެ', 'type' => 'person', 'channels' => ['email'], 'size' => 'l'],
            'kitchen-sink-company.html' => ['title' => 'ރާއްޖެ', 'type' => 'company', 'channels' => ['email'], 'size' => 'l', 'slug' => 'raajje'],
        ];

        foreach ($fixtures as $file => $record) {
            $html = KitchenSinkForm::make()->model($record)->action('/kitchen-sink')->toHtml();
            $html = preg_replace('/data-stencil-live="[^"]*token[^"]*"/', 'data-stencil-live="{&quot;url&quot;:&quot;\/stencil\/evaluate&quot;,&quot;token&quot;:&quot;test-token&quot;,&quot;debounce&quot;:500}"', $html);
            $path = __DIR__ . "/../js/fixtures/{$file}";

            if (getenv('STENCIL_UPDATE_FIXTURES')) {
                file_put_contents($path, $html);
            }

            $this->assertEquals($this->keysAndHashes(file_get_contents($path)), $this->keysAndHashes($html));
        }
    }

    /**
     * @return array<int, string>
     */
    protected function keysAndHashes(string $html): array
    {
        preg_match_all('/data-stencil-key="([^"]+)" data-stencil-hash="([^"]+)"/', $html, $matches, PREG_SET_ORDER);

        return array_map(fn (array $match) => "{$match[1]}:{$match[2]}", $matches);
    }
}

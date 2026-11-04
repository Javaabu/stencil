<?php

namespace Javaabu\Stencil\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Javaabu\Stencil\Exceptions\StencilException;
use Javaabu\Stencil\Fields\Field;
use Javaabu\Stencil\Form;
use Javaabu\Stencil\Tests\Fixtures\Fields\Rating;
use Javaabu\Stencil\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CustomFieldsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['view']->addNamespace('fixtures', __DIR__ . '/../Fixtures/views');
    }

    protected function form(): Form
    {
        return Form::make()->model(['stars' => 4])->schema([
            Rating::make('stars')->label('ތަރި')->max(10)->required()->help('1 އިން 10 އަށް'),
        ]);
    }

    #[Test]
    public function it_renders_a_custom_field_with_its_view(): void
    {
        $html = $this->form()->toHtml();

        $this->assertStringContainsString('ތަރި', $html);
        $this->assertMatchesRegularExpression('/name="stars"\s+value="4"\s+min="1"\s+max="10"/', $html);
        $this->assertStringContainsString('1 އިން 10 އަށް', $html);
    }

    #[Test]
    public function it_serializes_a_custom_field_for_js_drivers(): void
    {
        $node = $this->form()->toArray()['components'][0];

        $this->assertEquals('rating', $node['type']);
        $this->assertEquals('stars', $node['name']);
        $this->assertArrayNotHasKey('component', $node);
        $this->assertEquals(['label' => 'ތަރި', 'required' => true, 'help' => '1 އިން 10 އަށް', 'max' => 10], $node['props']);
    }

    #[Test]
    public function it_validates_a_custom_field(): void
    {
        $form = $this->form();

        $this->assertEquals(['required', 'integer', 'min:1', 'max:10'], $form->rules()['stars']);
        $this->assertEquals(['stars' => '7'], $form->validate(['stars' => '7']));
    }

    #[Test]
    public function it_explains_a_field_without_a_component_or_view(): void
    {
        $form = Form::make()->schema([ViewlessField::make('x')]);

        $this->expectException(StencilException::class);
        $this->expectExceptionMessage("Stencil::view('viewless'");

        $form->toHtml();
    }

    #[Test]
    public function it_generates_a_custom_field(): void
    {
        $files = new Filesystem;
        $paths = [
            app_path('Stencil/Fields/StarRating.php'),
            resource_path('views/stencil/fields/star-rating.blade.php'),
            resource_path('js/stencil/fields/StarRating.tsx'),
        ];

        $this->artisan('make:stencil-field', ['name' => 'StarRating'])->assertSuccessful();

        foreach ($paths as $path) {
            $this->assertFileExists($path);
        }

        $this->assertStringContainsString("protected string \$type = 'star-rating';", $files->get($paths[0]));
        $this->assertStringContainsString("protected ?string \$view = 'stencil.fields.star-rating';", $files->get($paths[0]));
        $this->assertStringContainsString('export function StarRating', $files->get($paths[2]));

        $this->artisan('make:stencil-field', ['name' => 'StarRating'])->expectsOutputToContain('--force')->assertSuccessful();

        $files->delete($paths);
    }
}

class ViewlessField extends Field
{
    protected string $type = 'viewless';
}

<?php

namespace Javaabu\Stencil\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Stencil\Form;
use Javaabu\Stencil\Tests\Fixtures\Components\FormCard;
use Javaabu\Stencil\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Stringable;

/**
 * Blade casts objects with __toString() to strings when they're passed to a
 * component attribute. A form passed to a component must arrive as the form.
 */
class BladeComponentAttributesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['view']->addNamespace('fixtures', __DIR__ . '/../Fixtures/views');

        Blade::component('fixtures-form-card', FormCard::class);
    }

    #[Test]
    public function it_passes_a_form_to_a_component_as_the_form(): void
    {
        $form = Form::make()->schema([TextInput::make('name')->label('ނަން')]);

        $html = Blade::render('<x-fixtures-form-card :form="$form" title="ފޯމު" />', ['form' => $form]);

        $this->assertStringContainsString('data-form-type="' . Form::class . '"', $html);
        $this->assertEquals(1, substr_count($html, '<form'));
        $this->assertStringContainsString('name="name"', $html);
    }

    #[Test]
    public function it_keeps_forms_out_of_stringable(): void
    {
        $this->assertNotInstanceOf(Stringable::class, Form::make());
        $this->assertFalse(method_exists(Form::class, '__toString'));
    }

    #[Test]
    public function it_still_renders_with_echo(): void
    {
        $html = Blade::render('{{ $form }}', ['form' => Form::make()->schema([TextInput::make('name')])]);

        $this->assertStringContainsString('name="name"', $html);
    }
}

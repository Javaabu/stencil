<?php

namespace Javaabu\Stencil\Tests\Feature;

use Javaabu\Stencil\Components\Group;
use Javaabu\Stencil\Components\Section;
use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Stencil\Form;
use Javaabu\Stencil\Tests\Fixtures\Models\Article;
use Javaabu\Stencil\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class StatePathTest extends TestCase
{
    protected function form(mixed $record = null): Form
    {
        return Form::make()->model($record)->schema([
            TextInput::make('title')->required(),
            Group::make()->statePath('address')->schema([
                TextInput::make('line')->label('އެޑްރެސް')->required()->maxLength(100),
                Checkbox::make('has_flat'),
                TextInput::make('flat')->visibleWhen('address.has_flat', true),
                Section::make('ރަށް')->statePath('island')->schema([
                    Select::make('code')->options(['K' => 'ކ', 'S' => 'ސ']),
                ]),
            ]),
        ]);
    }

    #[Test]
    public function it_nests_the_fields_inside_a_state_path(): void
    {
        $form = $this->form();

        $this->assertEquals(['title', 'address.line', 'address.has_flat', 'address.flat', 'address.island.code'], array_keys($form->getFields()));

        $line = $form->getField('address.line');

        $this->assertEquals('line', $line->getName());
        $this->assertEquals('address.line', $line->getStatePath());
        $this->assertEquals('address[line]', $line->getInputName());
        $this->assertEquals('address-line', $line->getId());
        $this->assertEquals('address.line', $line->getKey());
        $this->assertEquals('address[island][code]', $form->getField('address.island.code')->getInputName());
        $this->assertEquals('#address-has_flat', $form->toNode()->find('address.flat')->conditions[0]['selector']);
    }

    #[Test]
    public function it_fills_validates_and_saves_nested_state(): void
    {
        $form = $this->form(['title' => 'ސުރުހީ', 'address' => ['line' => 'މާލެ', 'island' => ['code' => 'K']]]);

        $this->assertEquals(['line' => 'މާލެ', 'has_flat' => null, 'flat' => null, 'island' => ['code' => 'K']], $form->getState()['address']);

        $rules = $form->rules();

        $this->assertEquals(['required', 'string', 'max:100'], $rules['address.line']);
        $this->assertArrayHasKey('address.island.code', $rules);
        $this->assertEquals('އެޑްރެސް', $form->validationAttributes()['address.line']);

        $data = $form->validate(['title' => 'ސުރުހީ', 'address' => ['line' => 'ހުޅުމާލެ', 'has_flat' => '1', 'flat' => '3A', 'island' => ['code' => 'S']]]);

        $this->assertEquals([
            'title' => 'ސުރުހީ',
            'address' => ['line' => 'ހުޅުމާލެ', 'has_flat' => true, 'flat' => '3A', 'island' => ['code' => 'S']],
        ], $data);

        $article = $form->fillModel(new Article, $data);

        $this->assertEquals('ހުޅުމާލެ', $article->address['line']);
    }

    #[Test]
    public function it_renders_nested_names_with_both_drivers(): void
    {
        $form = $this->form(['address' => ['line' => 'މާލެ']]);

        $html = $form->toHtml();

        $this->assertStringContainsString('name="address[line]"', $html);
        $this->assertStringContainsString('id="address-line"', $html);
        $this->assertStringContainsString('value="މާލެ"', $html);
        $this->assertStringContainsString('name="address[island][code]"', $html);

        $group = $form->toArray()['components'][1];

        $this->assertEquals('address[line]', $group['children'][0]['name']);
        $this->assertEquals('address.line', $group['children'][0]['key']);
    }

    #[Test]
    public function it_renames_a_field_with_its_own_state_path(): void
    {
        $form = Form::make()->schema([
            Group::make()->statePath('meta')->schema([TextInput::make('subtitle')->statePath('sub_title')]),
        ]);

        $this->assertEquals(['meta.sub_title'], array_keys($form->getFields()));
        $this->assertEquals('meta[sub_title]', $form->getField('meta.sub_title')->getInputName());
    }

    #[Test]
    public function it_tells_integer_inputs_apart(): void
    {
        $this->assertTrue(TextInput::make('views')->integer()->isInteger());
        $this->assertFalse(TextInput::make('price')->numeric()->isInteger());
        $this->assertFalse(TextInput::make('name')->isInteger());
    }

    #[Test]
    public function it_knows_a_field_was_declared_translatable(): void
    {
        $form = Form::make()->model(['title' => 'x'])->schema([
            TextInput::make('title')->translatable(),
            TextInput::make('slug'),
        ]);

        $this->assertTrue($form->getField('title')->isDeclaredTranslatable());
        $this->assertFalse($form->getField('title')->isTranslatable());
        $this->assertFalse($form->getField('slug')->isDeclaredTranslatable());
    }
}

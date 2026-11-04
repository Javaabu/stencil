<?php

namespace Javaabu\Stencil\Tests\Unit;

use Javaabu\Stencil\Components\Fieldset;
use Javaabu\Stencil\Components\Grid;
use Javaabu\Stencil\Components\Group;
use Javaabu\Stencil\Components\Section;
use Javaabu\Stencil\Components\Tab;
use Javaabu\Stencil\Components\Tabs;
use Javaabu\Stencil\Exceptions\StencilException;
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Stencil\Form;
use Javaabu\Stencil\Tests\Fixtures\Forms\ArticleForm;
use Javaabu\Stencil\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SchemaTest extends TestCase
{
    #[Test]
    public function it_collects_fields_at_any_depth(): void
    {
        $form = Form::make()->schema([
            Section::make('ތަފްޞީލް')->schema([
                Grid::make(2)->schema([
                    TextInput::make('name'),
                    Fieldset::make('އެޑްރެސް')->schema([
                        TextInput::make('address.city'),
                    ]),
                ]),
            ]),
            Tabs::make([Tab::make('Other')->schema([TextInput::make('note')])]),
        ]);

        $this->assertEquals(['name', 'address.city', 'note'], array_keys($form->getFields()));
        $this->assertSame($form, $form->getField('address[city]')->getForm());
    }

    #[Test]
    public function it_skips_null_components(): void
    {
        $form = Form::make()->schema([
            TextInput::make('name'),
            false ? TextInput::make('secret') : null,
        ]);

        $this->assertEquals(['name'], array_keys($form->getFields()));
    }

    #[Test]
    public function it_rejects_duplicate_field_names(): void
    {
        $form = Form::make()->schema([TextInput::make('name'), TextInput::make('name')]);

        $this->expectException(StencilException::class);

        $form->getFields();
    }

    #[Test]
    public function it_generates_keys_for_layout_components(): void
    {
        $form = Form::make()->schema([
            Section::make('ތަފްޞީލް')->schema([Grid::make()->schema([TextInput::make('name')])]),
            Group::make([TextInput::make('note')])->key('extra'),
            Tabs::make([Tab::make('Personal details')]),
        ]);

        $root = $form->toNode();

        $this->assertEquals('section-0', $root->children[0]->key);
        $this->assertEquals('grid-0-0', $root->children[0]->children[0]->key);
        $this->assertEquals('name', $root->children[0]->children[0]->children[0]->key);
        $this->assertEquals('extra', $root->children[1]->key);
        $this->assertEquals('tab-personal_details', $root->children[2]->children[0]->key);
    }

    #[Test]
    public function it_converts_names_between_dot_and_bracket_notation(): void
    {
        $field = TextInput::make('address[city]');

        $this->assertEquals('address.city', $field->getName());
        $this->assertEquals('address[city]', $field->getInputName());
        $this->assertEquals('address-city', $field->getId());
        $this->assertEquals('#address-city', $field->getSelector());
    }

    #[Test]
    public function it_generates_labels_like_javaabu_forms(): void
    {
        $form = Form::make()->schema([
            TextInput::make('first_name'),
            TextInput::make('address.postCode'),
            TextInput::make('island')->label('ރަށް'),
        ]);

        $this->assertEquals('First Name', $form->getField('first_name')->getLabel());
        $this->assertEquals('Post Code', $form->getField('address.postCode')->getLabel());
        $this->assertEquals('ރަށް', $form->getField('island')->getLabel());
    }

    #[Test]
    public function it_names_form_classes_after_the_class(): void
    {
        $this->assertEquals('article-form', ArticleForm::make()->getId());
        $this->assertEquals('stencil-form', Form::make()->getId());
        $this->assertEquals('edit-article', ArticleForm::make('edit-article')->getId());
    }

    #[Test]
    public function it_takes_columns_and_spans(): void
    {
        $this->assertNull(Grid::make(1)->getColumns());
        $this->assertEquals(['default' => 1, 'md' => 3], Grid::make(3)->getColumns());
        $this->assertEquals(['default' => 1, 'sm' => 2, 'xl' => 4], Grid::make(['sm' => 2, 'xl' => 4])->getColumns());
        $this->assertEquals(['md' => 'full'], TextInput::make('a')->columnSpanFull()->getColumnSpan());
        $this->assertEquals(['md' => 2, 'xl' => 1], TextInput::make('a')->columnSpan(['md' => 2, 'xl' => 1])->getColumnSpan());
    }

    #[Test]
    public function it_hides_components_by_operation(): void
    {
        $form = Form::make()->schema([
            TextInput::make('password')->password()->visibleOn('create'),
            TextInput::make('slug')->hiddenOn('create'),
        ]);

        $this->assertTrue($form->getField('password')->isVisible());
        $this->assertTrue($form->getField('slug')->isHidden());

        $form->operation('edit');

        $this->assertTrue($form->getField('password')->isHidden());
        $this->assertTrue($form->getField('slug')->isVisible());
    }

    #[Test]
    public function it_hides_everything_inside_a_hidden_component(): void
    {
        $form = Form::make()->schema([
            Section::make('ތަފްޞީލް')->hidden()->schema([TextInput::make('name')]),
        ]);

        $this->assertTrue($form->getField('name')->isHidden());
        $this->assertArrayNotHasKey('name', $form->rules());
    }

    #[Test]
    public function it_disables_every_field_for_the_view_operation(): void
    {
        $form = Form::make()->operation('view')->schema([TextInput::make('name')]);

        $this->assertTrue($form->getField('name')->isDisabled());
        $this->assertEquals([], $form->rules());
    }
}

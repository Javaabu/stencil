<?php

namespace Javaabu\Stencil\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Javaabu\Stencil\Components\Alert;
use Javaabu\Stencil\Components\Section;
use Javaabu\Stencil\Components\TextEntry;
use Javaabu\Stencil\Exceptions\StencilException;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Stencil\Form;
use Javaabu\Stencil\Get;
use Javaabu\Stencil\Tests\Fixtures\Forms\ArticleForm;
use Javaabu\Stencil\Tests\Fixtures\Models\Article;
use Javaabu\Stencil\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class JsonRendererTest extends TestCase
{
    #[Test]
    public function it_serializes_the_form_and_its_components(): void
    {
        $form = Form::make('company')
            ->model(['type' => 'company', 'name' => 'ޖާވާބު'])
            ->action('/companies/1')
            ->summary()
            ->schema([
                Section::make('ތަފްޞީލް')->description('ކުންފުނީގެ މަޢުލޫމާތު')->columns(2)->schema([
                    TextInput::make('name')->required()->maxLength(255)->thaana()->extraAttributes(['class' => 'font-bold', 'data-test' => 'name'])->columnSpanFull(),
                    Select::make('type')->options([2 => 'ކުންފުންޏެއް', 1 => 'ފަރުދެއް']),
                    TextInput::make('company_name')->visible(fn (Get $get) => $get('type') === 'person'),
                    TextInput::make('id_card')->visibleWhen('type', 'person'),
                ]),
                Alert::make('ސަމާލުކަމަށް')->color('warning'),
                TextEntry::make('name'),
            ]);

        $json = json_decode(json_encode($form), true);

        $this->assertEquals(1, $json['stencil']);
        $this->assertEquals('company', $json['id']);
        $this->assertEquals('/companies/1', $json['action']);
        $this->assertEquals('POST', $json['method']);
        $this->assertEquals('edit', $json['operation']);
        $this->assertEquals('Save', $json['submit']);
        $this->assertTrue($json['summary']);
        $this->assertArrayNotHasKey('files', $json);
        $this->assertEquals(['type' => 'company', 'name' => 'ޖާވާބު', 'company_name' => null, 'id_card' => null], $json['state']);

        $section = $json['components'][0];

        $this->assertEquals(['type' => 'section', 'key' => 'section-0'], array_intersect_key($section, ['type' => 1, 'key' => 1]));
        $this->assertEquals(['heading' => 'ތަފްޞީލް', 'description' => 'ކުންފުނީގެ މަޢުލޫމާތު', 'columns' => ['default' => 1, 'md' => 2]], $section['props']);

        [$name, $type, $company_name, $id_card] = $section['children'];

        $this->assertEquals([
            'type' => 'text',
            'key' => 'name',
            'name' => 'name',
            'component' => 'Text',
            'props' => [
                'label' => 'Name',
                'required' => true,
                'thaana' => true,
                'maxLength' => 255,
                'className' => 'font-bold',
                'data-test' => 'name',
            ],
            'span' => ['md' => 'full'],
        ], $name);

        $this->assertEquals([['value' => '2', 'label' => 'ކުންފުންޏެއް'], ['value' => '1', 'label' => 'ފަރުދެއް']], $type['props']['options']);
        $this->assertArrayNotHasKey('live', $type);

        $this->assertEquals(['type' => 'text', 'key' => 'company_name', 'name' => 'company_name', 'hidden' => true], $company_name);

        $this->assertEquals('visible', $id_card['conditions'][0]['effect']);
        $this->assertEquals('#type', $id_card['conditions'][0]['selector']);

        $this->assertEquals(['message' => 'ސަމާލުކަމަށް', 'type' => 'warning'], $json['components'][1]['props']);
        $this->assertEquals('ޖާވާބު', $json['components'][2]['props']['value']);
    }

    #[Test]
    public function it_leaves_out_empty_props(): void
    {
        $json = Form::make()->schema([Section::make()])->toArray();

        $this->assertEquals(['type' => 'section', 'key' => 'section-0'], $json['components'][0]);
    }

    #[Test]
    public function it_adds_the_live_endpoint_and_a_token_for_form_classes(): void
    {
        $this->setUpDatabase();

        Route::stencil();

        $json = ArticleForm::make()->model(new Article)->toArray();

        $this->assertEquals('/stencil/evaluate', $json['live']['url']);
        $this->assertEquals(500, $json['live']['debounce']);
        $this->assertNotEmpty($json['live']['token']);
    }

    #[Test]
    public function it_needs_a_form_class_for_live_fields(): void
    {
        Route::stencil();

        $this->expectException(StencilException::class);
        $this->expectExceptionMessage('[type] is live');

        Form::make()->schema([TextInput::make('type')->live()])->toArray();
    }

    #[Test]
    public function it_needs_the_live_route(): void
    {
        $this->setUpDatabase();

        $this->expectException(StencilException::class);
        $this->expectExceptionMessage('Route::stencil()');

        ArticleForm::make()->model(Article::class)->toArray();
    }

    #[Test]
    public function it_names_multiple_selects_with_brackets(): void
    {
        $json = Form::make()->schema([Select::make('tags')->multiple()->options(['a' => 'A'])->searchable()])->toArray();

        $this->assertEquals('tags[]', $json['components'][0]['name']);
        $this->assertEquals('Select2', $json['components'][0]['component']);
        $this->assertTrue($json['components'][0]['props']['multiple']);
    }

    #[Test]
    public function it_works_as_an_inertia_prop(): void
    {
        $form = Form::make()->schema([TextInput::make('name')]);

        $this->assertEquals($form->toArray(), $form->jsonSerialize());
        $this->assertEquals('text', $form->render('inertia')['components'][0]['type']);
    }
}

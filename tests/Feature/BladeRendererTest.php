<?php

namespace Javaabu\Stencil\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use Javaabu\Manifest\Facades\Manifest;
use Javaabu\Stencil\Components\Alert;
use Javaabu\Stencil\Components\Fieldset;
use Javaabu\Stencil\Components\Grid;
use Javaabu\Stencil\Components\Section;
use Javaabu\Stencil\Components\Step;
use Javaabu\Stencil\Components\Tab;
use Javaabu\Stencil\Components\Tabs;
use Javaabu\Stencil\Components\TextEntry;
use Javaabu\Stencil\Components\Wizard;
use Javaabu\Stencil\Contracts\Renderer;
use Javaabu\Stencil\Facades\Stencil;
use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\CheckboxList;
use Javaabu\Stencil\Fields\DatePicker;
use Javaabu\Stencil\Fields\FileUpload;
use Javaabu\Stencil\Fields\Hidden;
use Javaabu\Stencil\Fields\Radio;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Fields\Textarea;
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Stencil\Fields\Wysiwyg;
use Javaabu\Stencil\Form;
use Javaabu\Stencil\Get;
use Javaabu\Stencil\Tests\Fixtures\Forms\ArticleForm;
use Javaabu\Stencil\Tests\Fixtures\Models\Article;
use Javaabu\Stencil\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class BladeRendererTest extends TestCase
{
    #[Test]
    public function it_renders_a_form_with_javaabu_forms(): void
    {
        $html = Form::make('company')
            ->model(['name' => 'ޖާވާބު', 'type' => 'company'])
            ->action('/companies/1')
            ->method('PUT')
            ->schema([
                TextInput::make('name')->label('ނަން')->required()->thaana()->help('ފުރިހަމަ ނަން')->prefix('@'),
                Select::make('type')->options(['person' => 'ފަރުދެއް', 'company' => 'ކުންފުންޏެއް']),
            ])
            ->toHtml();

        $this->assertStringContainsString('<form', $html);
        $this->assertStringContainsString('id="company"', $html);
        $this->assertStringContainsString('action="/companies/1"', $html);
        $this->assertStringContainsString('name="_method" value="PUT"', $html);
        $this->assertStringContainsString('value="ޖާވާބު"', $html);
        $this->assertStringContainsString('ނަން', $html);
        $this->assertStringContainsString('ފުރިހަމަ ނަން', $html);
        $this->assertStringContainsString('data-thaana', $html);
        $this->assertMatchesRegularExpression('/value="company"[^>]*selected/', $html);
        $this->assertStringContainsString('data-stencil-key="name"', $html);
        $this->assertStringContainsString('Save', $html);
    }

    #[Test]
    public function it_renders_layouts_and_grids(): void
    {
        $html = Form::make()->submit(false)->schema([
            Section::make('ތަފްޞީލް')->description('ކުރު ތަފްޞީލް')->columns(['md' => 2, 'xl' => 3])->schema([
                TextInput::make('name')->columnSpan(2),
            ]),
            Grid::make(2)->schema([TextInput::make('a')]),
            Fieldset::make('އެޑްރެސް')->schema([TextInput::make('address.city')]),
            Tabs::make([
                Tab::make('Personal')->schema([TextInput::make('b')]),
                Tab::make('Hidden')->hidden()->schema([TextInput::make('c')]),
            ]),
        ])->toHtml();

        $this->assertStringContainsString('ތަފްޞީލް', $html);
        $this->assertStringContainsString('ކުރު ތަފްޞީލް', $html);
        $this->assertStringContainsString('--stencil-cols: 1; --stencil-cols-md: 2; --stencil-cols-xl: 3', $html);
        $this->assertStringContainsString('--stencil-span-md: span 2 / span 2', $html);
        $this->assertStringContainsString('<legend class="stencil-legend">އެޑްރެސް</legend>', $html);
        $this->assertStringContainsString('name="address[city]"', $html);
        $this->assertStringContainsString('id="tab-personal"', $html);
        $this->assertStringContainsString('name="b"', $html);
        $this->assertStringNotContainsString('name="c"', $html);
        $this->assertStringNotContainsString('Save', $html);
    }

    #[Test]
    public function it_renders_every_field_type(): void
    {
        $html = Form::make()
            ->model(['channels' => ['email'], 'size' => 'l', 'agree' => true])
            ->schema([
                TextInput::make('email')->email(),
                TextInput::make('quantity')->numeric()->minValue(1),
                Textarea::make('bio')->rows(5)->thaana(),
                Wysiwyg::make('body'),
                Checkbox::make('agree'),
                CheckboxList::make('channels')->options(['sms' => 'SMS', 'email' => 'Email'])->gridColumns(2),
                Radio::make('size')->options(['s' => 'ކުޑަ', 'l' => 'ބޮޑު']),
                Select::make('tags')->multiple()->searchable()->options(['a' => 'A']),
                DatePicker::make('born_on'),
                FileUpload::make('cv'),
                Hidden::make('token')->default('abc'),
                TextEntry::make('size')->label('ސައިޒް'),
                Alert::make('ސަމާލުކަމަށް')->color('warning'),
            ])
            ->toHtml();

        $this->assertStringContainsString('type="email"', $html);
        $this->assertStringContainsString('type="number"', $html);
        $this->assertStringContainsString('min="1"', $html);
        $this->assertStringContainsString('rows="5"', $html);
        $this->assertStringContainsString('name="body"', $html);
        $this->assertMatchesRegularExpression('/id="agree"[^>]*value="1"\s+checked/', $html);
        $this->assertMatchesRegularExpression('/id="channels-email"\s+name="channels\[\]"\s+type="checkbox"\s+value="email"\s+checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="channels-sms"[^>]*checked/', $html);
        $this->assertMatchesRegularExpression('/id="size-l"\s+name="size"\s+type="radio"\s+value="l"\s+checked/', $html);
        $this->assertStringContainsString('name="tags[]"', $html);
        $this->assertStringContainsString('multiple', $html);
        $this->assertStringContainsString('name="born_on"', $html);
        $this->assertStringContainsString('type="file"', $html);
        $this->assertStringContainsString('enctype="multipart/form-data"', $html);
        $this->assertStringContainsString('value="abc"', $html);
        $this->assertStringContainsString('ބޮޑު', $html);
        $this->assertStringContainsString('ސަމާލުކަމަށް', $html);
    }

    #[Test]
    public function it_wraps_conditional_components_in_forms_conditional_wrapper(): void
    {
        $html = Form::make()->schema([
            Select::make('type')->options(['person' => 'ފަރުދެއް', 'company' => 'ކުންފުންޏެއް']),
            Checkbox::make('has_address'),
            TextInput::make('id_card')->visibleWhen('type', 'person'),
            TextInput::make('company_name')->hiddenWhen('type', ['person', 'other']),
            TextInput::make('address')->enabledWhenChecked('has_address'),
        ])->toHtml();

        $this->assertMatchesRegularExpression('/data-enable-elem="#type"\s+data-enable-section-value="person"\s+data-hide-fields="true"/', $html);
        $this->assertStringContainsString('data-disable="true"', $html);
        $this->assertStringContainsString('data-enable-section-checkbox="#has_address"', $html);
    }

    #[Test]
    public function it_leaves_out_hidden_components(): void
    {
        $html = Form::make()->model(['type' => 'person'])->schema([
            TextInput::make('type'),
            TextInput::make('company_name')->visible(fn (Get $get) => $get('type') === 'company'),
        ])->toHtml();

        $this->assertStringNotContainsString('company_name', $html);
    }

    #[Test]
    public function it_renders_the_wizard_and_requires_its_script(): void
    {
        Manifest::fake();

        $html = Form::make()->schema([
            Wizard::make([
                Step::make('އެކައުންޓް')->description('ފުރަތަމަ')->schema([TextInput::make('email')]),
                Step::make('ޕްރޮފައިލް')->schema([TextInput::make('name')]),
            ]),
        ])->toHtml();

        $this->assertStringContainsString('data-stencil-wizard', $html);
        $this->assertStringContainsString('އެކައުންޓް', $html);
        $this->assertMatchesRegularExpression('/data-stencil-wizard-panel="1"\s+hidden/', $html);
        $this->assertStringContainsString('Next', $html);

        Manifest::assertAssetRequired('stencil::stencil');
        Manifest::assertAssetRequired('stencil::styles');
    }

    #[Test]
    public function it_renders_only_the_fields_of_an_embedded_form(): void
    {
        $html = Form::make()->embedded()->model(['search' => 'މާލެ'])->schema([TextInput::make('search')])->toHtml();

        $this->assertStringNotContainsString('<form', $html);
        $this->assertStringContainsString('value="މާލެ"', $html);
    }

    #[Test]
    public function it_marks_live_forms_and_keeps_placeholders_for_hidden_components(): void
    {
        $this->setUpDatabase();

        Route::stencil();

        $html = ArticleForm::make()->model(new Article(['status' => 'draft']))->toHtml();

        $this->assertStringContainsString('data-stencil-live="', $html);
        $this->assertStringContainsString('data-stencil-key="published_at" data-stencil-hash="hidden" hidden', $html);
        $this->assertMatchesRegularExpression('/data-stencil-key="status"[^>]*data-stencil-live/', $html);
    }

    #[Test]
    public function it_renders_a_type_with_a_registered_view(): void
    {
        $this->app['view']->addNamespace('fixtures', __DIR__ . '/../Fixtures/views');

        Stencil::view('text', 'fixtures::custom-text');

        $html = Form::make()->schema([TextInput::make('name')])->toHtml();

        $this->assertStringContainsString('<custom-text name="name"></custom-text>', $html);
    }

    #[Test]
    public function it_takes_custom_drivers(): void
    {
        Stencil::extend('names', fn () => new class implements Renderer
        {
            public function render(Form $form): mixed
            {
                return new HtmlString(implode(',', array_keys($form->getFields())));
            }
        });

        $form = Form::make()->schema([TextInput::make('name'), TextInput::make('email')]);

        $this->assertEquals('name,email', (string) $form->render('names'));

        config(['stencil.views' => 'names']);

        $this->assertEquals('name,email', (string) Stencil::render($form));
    }
}

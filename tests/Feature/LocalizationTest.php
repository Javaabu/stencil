<?php

namespace Javaabu\Stencil\Tests\Feature;

use Javaabu\Stencil\Components\Step;
use Javaabu\Stencil\Components\Wizard;
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Stencil\Form;
use Javaabu\Stencil\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class LocalizationTest extends TestCase
{
    #[Test]
    public function it_has_the_same_keys_in_english_and_dhivehi(): void
    {
        $en = require __DIR__ . '/../../lang/en/strings.php';
        $dv = require __DIR__ . '/../../lang/dv/strings.php';

        $this->assertEquals(array_keys($en), array_keys($dv));
        $this->assertNotEquals($en, $dv);
    }

    #[Test]
    public function it_renders_its_strings_in_dhivehi(): void
    {
        $this->app->setLocale('dv');

        $form = Form::make()->schema([
            Wizard::make([
                Step::make('އެކައުންޓް')->schema([TextInput::make('email')]),
                Step::make('ޕްރޮފައިލް')->schema([TextInput::make('name')]),
            ]),
        ]);

        $html = $form->toHtml();

        $this->assertStringContainsString('ސޭވް ކުރައްވާ', $html);
        $this->assertStringContainsString('ކުރިއަށް', $html);
        $this->assertStringContainsString('ފަހަތަށް', $html);
        $this->assertStringContainsString('ސްޓެޕް 1 / 2', $html);

        $wizard = $form->toArray()['components'][0]['props'];

        $this->assertEquals('ކުރިއަށް', $wizard['nextLabel']);
        $this->assertEquals('ސްޓެޕް :current / :total', $wizard['stepLabel']);
    }

    #[Test]
    public function it_translates_generated_labels_and_validation_attributes(): void
    {
        $this->app->setLocale('dv');
        $this->app['translator']->addLines(['*.First Name' => 'ފުރަތަމަ ނަން'], 'dv');

        $form = Form::make()->schema([TextInput::make('first_name')->required()]);

        $this->assertEquals('ފުރަތަމަ ނަން', $form->getField('first_name')->getLabel());
        $this->assertEquals(['first_name' => 'ފުރަތަމަ ނަން'], $form->validationAttributes());
    }

    #[Test]
    public function it_marks_thaana_fields_for_rtl_typing(): void
    {
        $html = Form::make()->schema([TextInput::make('name')->thaana(), TextInput::make('email')->email()->thaana()])->toHtml();

        $this->assertMatchesRegularExpression('/dir="rtl"\s+lang="dv"\s+data-thaana/', $html);
        $this->assertDoesNotMatchRegularExpression('/type="email"[^>]*data-thaana/', $html);
    }
}

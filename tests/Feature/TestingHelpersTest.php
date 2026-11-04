<?php

namespace Javaabu\Stencil\Tests\Feature;

use Javaabu\Stencil\Components\Section;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Stencil\Form;
use Javaabu\Stencil\Get;
use Javaabu\Stencil\Testing\InteractsWithStencil;
use Javaabu\Stencil\Tests\TestCase;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\Test;

class TestingHelpersTest extends TestCase
{
    use InteractsWithStencil;

    protected function form(): Form
    {
        return Form::make()->schema([
            Section::make('ތަފްޞީލް')->schema([
                TextInput::make('title')->label('ސުރުހީ')->required()->maxLength(255)->thaana(),
                Select::make('type')->options(['person' => 'ފަރުދެއް', 'company' => 'ކުންފުންޏެއް'])->live(),
                TextInput::make('company_name')->visibleWhen('type', 'company')->required(),
                TextInput::make('reason')->visible(fn (Get $get) => $get('type') === 'person'),
                TextInput::make('code')->disabled(),
            ]),
        ]);
    }

    #[Test]
    public function it_asserts_the_schema(): void
    {
        $this->assertForm($this->form())
            ->assertFields(['title', 'type', 'company_name', 'reason', 'code'])
            ->assertHasField('title', TextInput::class)
            ->assertMissingField('slug')
            ->assertHasComponent('section-0')
            ->assertFieldRequired('title')
            ->assertFieldNotRequired('type')
            ->assertFieldLive('type')
            ->assertFieldLabel('title', 'ސުރުހީ')
            ->assertFieldProp('title', 'thaana', true)
            ->assertOptions('type', ['person' => 'ފަރުދެއް', 'company' => 'ކުންފުންޏެއް'])
            ->assertFieldDisabled('code')
            ->assertFieldEnabled('title');
    }

    #[Test]
    public function it_asserts_visibility_for_a_state(): void
    {
        $this->form()->assertable()
            ->fillState(['type' => 'person'])
            ->assertFieldHidden('company_name')
            ->assertFieldVisible('reason')
            ->assertNotValidated('company_name')
            ->assertState('type', 'person')
            ->fillState(['type' => 'company'])
            ->assertFieldVisible('company_name')
            ->assertFieldHidden('reason')
            ->assertRules('company_name', ['required', 'string'])
            ->assertHasRule('type', 'in:"person","company"');
    }

    #[Test]
    public function it_asserts_validation(): void
    {
        $this->assertForm($this->form())
            ->assertValid(['title' => 'ސުރުހީ', 'type' => 'person'])
            ->assertInvalid(['type' => 'company'], ['title', 'company_name'])
            ->assertValidated(['title' => 'ސުރުހީ', 'type' => 'person', 'reason' => 'ސަބަބު'], [
                'title' => 'ސުރުހީ',
                'type' => 'person',
                'reason' => 'ސަބަބު',
            ]);
    }

    #[Test]
    public function it_asserts_the_output(): void
    {
        $form = Form::make()->model(['type' => 'company'])->schema([
            Select::make('type')->options(['person' => 'ފަރުދެއް', 'company' => 'ކުންފުންޏެއް']),
            TextInput::make('reason')->visible(fn (Get $get) => $get('type') === 'person'),
        ]);

        $this->assertForm($form)
            ->assertRendersField('type')
            ->assertDoesNotRenderField('reason')
            ->assertSee('ކުންފުންޏެއް')
            ->assertJsonPath('components.0.props.options.1.label', 'ކުންފުންޏެއް')
            ->assertJsonPath('components.1.hidden', true);
    }

    #[Test]
    public function it_fails_with_a_clear_message(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The field [title] is optional.');

        $this->assertForm(Form::make()->schema([TextInput::make('title')]))->assertFieldRequired('title');
    }

    #[Test]
    public function it_fails_for_a_missing_field(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The form has no field [nope].');

        $this->assertForm($this->form())->assertFieldVisible('nope');
    }
}

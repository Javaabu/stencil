<?php

namespace Javaabu\Stencil\Tests\Unit;

use Javaabu\Stencil\Components\Section;
use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\CheckboxList;
use Javaabu\Stencil\Fields\Radio;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Stencil\Form;
use Javaabu\Stencil\Tests\Fixtures\Enums\ArticleStatuses;
use Javaabu\Stencil\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ConditionsTest extends TestCase
{
    protected function form(): Form
    {
        return Form::make()->schema([
            Radio::make('type')->options(['person' => 'ފަރުދެއް', 'company' => 'ކުންފުންޏެއް']),
            Select::make('status')->options(ArticleStatuses::class),
            Checkbox::make('has_address'),
            CheckboxList::make('channels')->options(['sms' => 'SMS', 'email' => 'Email']),
            TextInput::make('id_card')->visibleWhen('type', 'person')->required(),
            TextInput::make('company_name')->hiddenWhen('type', ['person'])->required(),
            TextInput::make('published_on')->enabledWhen('status', ArticleStatuses::PUBLISHED),
            TextInput::make('reason')->disabledWhen('status', ArticleStatuses::PUBLISHED),
            Section::make('އެޑްރެސް')->visibleWhenChecked('has_address')->schema([
                TextInput::make('address.city')->required(),
            ]),
            TextInput::make('phone')->visibleWhen('channels', 'sms'),
        ]);
    }

    #[Test]
    public function it_renders_components_whatever_their_conditions(): void
    {
        $form = $this->form()->fill(['type' => 'company']);

        $this->assertFalse($form->getField('id_card')->isHidden());
        $this->assertFalse($form->toNode()->find('id_card')->hidden);
    }

    #[Test]
    public function it_leaves_fields_hidden_by_conditions_out_of_validation(): void
    {
        $form = $this->form()->fill(['type' => 'company', 'status' => 'draft', 'has_address' => '0']);

        $rules = $form->rules();

        $this->assertArrayNotHasKey('id_card', $rules);
        $this->assertArrayHasKey('company_name', $rules);
        $this->assertArrayNotHasKey('published_on', $rules);
        $this->assertArrayHasKey('reason', $rules);
        $this->assertArrayNotHasKey('address.city', $rules);
        $this->assertArrayNotHasKey('phone', $rules);

        $form->fill(['type' => 'person', 'status' => 'published', 'has_address' => '1', 'channels' => ['email', 'sms']]);

        $rules = $form->rules();

        $this->assertArrayHasKey('id_card', $rules);
        $this->assertArrayNotHasKey('company_name', $rules);
        $this->assertArrayHasKey('published_on', $rules);
        $this->assertArrayNotHasKey('reason', $rules);
        $this->assertArrayHasKey('address.city', $rules);
        $this->assertArrayHasKey('phone', $rules);
    }

    #[Test]
    public function it_gives_drivers_the_selector_of_the_watched_field(): void
    {
        $node = $this->form()->toNode();

        $this->assertEquals([[
            'effect' => 'visible',
            'field' => 'type',
            'selector' => '[name="type"]',
            'values' => ['person'],
            'checkbox' => false,
        ]], $node->find('id_card')->conditions);

        $this->assertEquals('#status', $node->find('published_on')->conditions[0]['selector']);
        $this->assertEquals(['published'], $node->find('published_on')->conditions[0]['values']);
        $this->assertTrue($node->find('section-8')->conditions[0]['checkbox']);
        $this->assertEquals('#has_address', $node->find('section-8')->conditions[0]['selector']);
        $this->assertEquals('[name="channels[]"]', $node->find('phone')->conditions[0]['selector']);
    }

    #[Test]
    public function it_does_not_render_fields_disabled_by_a_condition_as_disabled(): void
    {
        $form = $this->form()->fill(['status' => 'draft']);

        $this->assertTrue($form->getField('published_on')->isDisabled());
        $this->assertArrayNotHasKey('disabled', $form->toNode()->find('published_on')->props);
    }
}

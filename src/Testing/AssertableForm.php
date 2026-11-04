<?php

namespace Javaabu\Stencil\Testing;

use BackedEnum;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Fields\Field;
use Javaabu\Stencil\Form;
use PHPUnit\Framework\Assert;
use Stringable;

/**
 * Assertions on a form's schema, state, validation and output, for apps and
 * packages built on stencil: AssertableForm::make($form)->fillState([...])->assertFieldVisible('company_name').
 */
class AssertableForm
{
    public function __construct(
        protected Form $form,
    ) {}

    public static function make(Form $form): static
    {
        return new static($form);
    }

    public function form(): Form
    {
        return $this->form;
    }

    /**
     * Set the state that closures and conditions are evaluated against.
     *
     * @param  array<string, mixed>  $state
     */
    public function fillState(array $state): static
    {
        $this->form->fill([...$this->form->getState(), ...$state]);

        return $this;
    }

    public function field(string $name): Field
    {
        $field = $this->form->getField($name);

        Assert::assertNotNull($field, "The form has no field [{$name}].");

        return $field;
    }

    /*
    |--------------------------------------------------------------------------
    | Schema
    |--------------------------------------------------------------------------
    */

    /**
     * @param  class-string<Field>|null  $type  the field's class, or one of its parents
     */
    public function assertHasField(string $name, ?string $type = null): static
    {
        $field = $this->field($name);

        if ($type !== null) {
            Assert::assertInstanceOf($type, $field, "The field [{$name}] is a [" . $field::class . "], not a [{$type}].");
        }

        return $this;
    }

    public function assertMissingField(string $name): static
    {
        Assert::assertNull($this->form->getField($name), "The form has a field [{$name}].");

        return $this;
    }

    /**
     * The form's fields, in order, are exactly these.
     *
     * @param  array<int, string>  $names
     */
    public function assertFields(array $names): static
    {
        Assert::assertEquals($names, array_keys($this->form->getFields()));

        return $this;
    }

    /**
     * A layout or display component with this key exists (section-0, tab-details, …).
     */
    public function assertHasComponent(string $key): static
    {
        Assert::assertNotNull($this->findComponent($key), "The form has no component [{$key}].");

        return $this;
    }

    /**
     * Visible for the current state: rendered, and not hidden by a condition.
     */
    public function assertFieldVisible(string $name): static
    {
        $field = $this->field($name);

        Assert::assertFalse($field->isHidden(), "The field [{$name}] is hidden.");
        Assert::assertFalse($field->isHiddenByConditions(), "The field [{$name}] is hidden by a condition.");

        return $this;
    }

    /**
     * Hidden for the current state: not rendered, or hidden by a condition.
     */
    public function assertFieldHidden(string $name): static
    {
        $field = $this->field($name);

        Assert::assertTrue($field->isHidden() || $field->isHiddenByConditions(), "The field [{$name}] is visible.");

        return $this;
    }

    public function assertFieldDisabled(string $name): static
    {
        Assert::assertTrue($this->field($name)->isDisabled(), "The field [{$name}] is enabled.");

        return $this;
    }

    public function assertFieldEnabled(string $name): static
    {
        Assert::assertFalse($this->field($name)->isDisabled(), "The field [{$name}] is disabled.");

        return $this;
    }

    public function assertFieldRequired(string $name): static
    {
        Assert::assertTrue($this->field($name)->isRequired(), "The field [{$name}] is optional.");

        return $this;
    }

    public function assertFieldNotRequired(string $name): static
    {
        Assert::assertFalse($this->field($name)->isRequired(), "The field [{$name}] is required.");

        return $this;
    }

    public function assertFieldLive(string $name): static
    {
        Assert::assertTrue($this->field($name)->isLive(), "The field [{$name}] isn't live.");

        return $this;
    }

    public function assertFieldLabel(string $name, string $label): static
    {
        Assert::assertEquals($label, $this->field($name)->getLabel());

        return $this;
    }

    /**
     * A prop the drivers get, after closures are evaluated: assertFieldProp('title', 'thaana', true).
     */
    public function assertFieldProp(string $name, string $prop, mixed $value): static
    {
        $props = $this->field($name)->toNode()->props;

        Assert::assertArrayHasKey($prop, $props, "The field [{$name}] has no prop [{$prop}].");
        Assert::assertEquals($value, $props[$prop]);

        return $this;
    }

    /**
     * @param  array<int|string, string>  $options  [value => label]
     */
    public function assertOptions(string $name, array $options): static
    {
        $field = $this->field($name);

        Assert::assertTrue(method_exists($field, 'getOptions'), "The field [{$name}] has no options.");
        Assert::assertEquals($options, $field->getOptions());

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | State and validation
    |--------------------------------------------------------------------------
    */

    public function assertState(string $name, mixed $value): static
    {
        Assert::assertEquals($value, $this->form->getStateValue($name));

        return $this;
    }

    /**
     * The field's rules for the current state, compared as strings: assertRules('slug', ['required', 'string', 'max:255']).
     *
     * @param  array<int, mixed>  $rules
     */
    public function assertRules(string $name, array $rules): static
    {
        Assert::assertEquals($this->stringifyRules($rules), $this->stringifyRules($this->rulesFor($name)));

        return $this;
    }

    public function assertHasRule(string $name, mixed $rule): static
    {
        $rule = $this->stringifyRule($rule);

        Assert::assertContains($rule, $this->stringifyRules($this->rulesFor($name)), "The field [{$name}] has no rule [{$rule}].");

        return $this;
    }

    public function assertNotValidated(string $name): static
    {
        Assert::assertArrayNotHasKey(Field::normalizeName($name), $this->form->rules(), "The field [{$name}] is validated.");

        return $this;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function assertValid(array $input): static
    {
        try {
            $this->form->validate($input);
        } catch (ValidationException $exception) {
            Assert::fail('The input is invalid: ' . json_encode($exception->errors(), JSON_UNESCAPED_UNICODE));
        }

        return $this;
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  string|array<int, string>  $fields  the fields that must have errors
     */
    public function assertInvalid(array $input, string|array $fields = []): static
    {
        try {
            $this->form->validate($input);
        } catch (ValidationException $exception) {
            foreach (Arr::wrap($fields) as $field) {
                Assert::assertArrayHasKey(Field::normalizeName($field), $exception->errors(), "The field [{$field}] has no error.");
            }

            return $this;
        }

        Assert::fail('The input is valid.');
    }

    /**
     * What validate() returns for the input.
     *
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $data
     */
    public function assertValidated(array $input, array $data): static
    {
        Assert::assertEquals($data, $this->form->validate($input));

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Output
    |--------------------------------------------------------------------------
    */

    /**
     * The Blade driver renders the field's control.
     */
    public function assertRendersField(string $name): static
    {
        $field = $this->field($name);

        Assert::assertStringContainsString('name="' . e($field->getInputName()) . '"', $this->form->toHtml());

        return $this;
    }

    public function assertDoesNotRenderField(string $name): static
    {
        $field = $this->field($name);

        Assert::assertStringNotContainsString('name="' . e($field->getInputName()) . '"', $this->form->toHtml());

        return $this;
    }

    /**
     * The Blade output contains the text.
     */
    public function assertSee(string $text): static
    {
        Assert::assertStringContainsString(e($text), $this->form->toHtml());

        return $this;
    }

    /**
     * A value in the JSON driver's output: assertJsonPath('components.0.props.heading', 'ތަފްޞީލް').
     */
    public function assertJsonPath(string $path, mixed $value): static
    {
        Assert::assertEquals($value, data_get($this->form->toArray(), $path));

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<int, mixed>
     */
    protected function rulesFor(string $name): array
    {
        $rules = $this->form->rules();
        $path = Field::normalizeName($name);

        Assert::assertArrayHasKey($path, $rules, "The field [{$name}] isn't validated for the current state.");

        return $rules[$path];
    }

    /**
     * @param  array<int, mixed>  $rules
     * @return array<int, string>
     */
    protected function stringifyRules(array $rules): array
    {
        return array_values(array_map($this->stringifyRule(...), $rules));
    }

    protected function stringifyRule(mixed $rule): string
    {
        if (is_string($rule)) {
            return $rule;
        }

        if ($rule instanceof Stringable || method_exists($rule, '__toString')) {
            return (string) $rule;
        }

        if ($rule instanceof BackedEnum) {
            return (string) $rule->value;
        }

        return is_object($rule) ? $rule::class : json_encode($rule);
    }

    protected function findComponent(string $key, ?Component $parent = null): ?Component
    {
        foreach (($parent ?? $this->form)->getChildComponents() as $component) {
            if ($component->getKey() === $key) {
                return $component;
            }

            $found = $this->findComponent($key, $component);

            if ($found) {
                return $found;
            }
        }

        return null;
    }
}

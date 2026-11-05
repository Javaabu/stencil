<?php

namespace Javaabu\Stencil\Fields\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Javaabu\Stencil\Contracts\ResolvesTranslatableFields;
use Javaabu\Translatable\LocaleInput;
use Javaabu\Translatable\Translatable;

/**
 * Optional javaabu/translatable support: a translatable field has one input per
 * active locale (name_en, name_dv). Without the package, or for a model or
 * field that isn't translatable, the field is a plain field.
 */
trait CanBeTranslatable
{
    protected bool|Closure $is_translatable = false;

    protected string $translatable_layout = 'tabs';

    protected bool $translatable_require_all = false;

    /**
     * One input per locale when the form's model translates this field.
     *
     * @param  string  $layout  tabs, or stacked for one input after another
     * @param  bool  $require_all  required in every locale, not only the model's primary one
     */
    public function translatable(bool|Closure $condition = true, string $layout = 'tabs', bool $require_all = false): static
    {
        $this->is_translatable = $condition;
        $this->translatable_layout = $layout;
        $this->translatable_require_all = $require_all;

        return $this;
    }

    /**
     * Whether translatable() was called (and its condition holds), whether or not
     * javaabu/translatable is installed or the model translates the field.
     */
    public function isDeclaredTranslatable(): bool
    {
        return (bool) $this->evaluate($this->is_translatable);
    }

    /**
     * Whether the field has one input per locale: it's declared translatable,
     * javaabu/translatable is installed, and the form's model translates the
     * field (or the record or form says so with ResolvesTranslatableFields).
     */
    public function isTranslatable(): bool
    {
        if (! $this->isDeclaredTranslatable()) {
            return false;
        }

        if (! class_exists(Translatable::class)) {
            return false;
        }

        $resolver = $this->translatableResolver();

        if ($resolver !== null) {
            return Translatable::enabled() && $resolver->isTranslatableField($this->getStatePath());
        }

        $model = $this->translatableModel();

        return $model !== null && Translatable::supports($model, $this->getStatePath());
    }

    /**
     * The form or record that decides which fields are translatable, if either does.
     */
    protected function translatableResolver(): ?ResolvesTranslatableFields
    {
        $form = $this->getForm();

        if ($form instanceof ResolvesTranslatableFields) {
            return $form;
        }

        $record = $form->getRecord();

        return $record instanceof ResolvesTranslatableFields ? $record : null;
    }

    /**
     * The model (or model class) translatable asks about.
     */
    protected function translatableModel(): object|string|null
    {
        $form = $this->getForm();
        $record = $form->getRecord();

        return $record instanceof Model ? $record : $form->getModelClass();
    }

    /**
     * The inputs, the model's primary locale first.
     *
     * @return array<string, LocaleInput>
     */
    public function getTranslatableInputs(): array
    {
        if (! $this->isTranslatable()) {
            return [];
        }

        return Translatable::inputs($this->getStatePath(), $this->translatableModel())->all();
    }

    protected function primaryTranslatableInput(): ?LocaleInput
    {
        foreach ($this->getTranslatableInputs() as $input) {
            if ($input->is_primary) {
                return $input;
            }
        }

        return null;
    }

    /**
     * The inputs as drivers get them, each with its label: "Name (ދިވެހި)".
     *
     * @return array{layout: string, inputs: array<int, array<string, mixed>>}|null
     */
    public function getTranslatableForDriver(): ?array
    {
        if (! $this->isTranslatable()) {
            return null;
        }

        $label = $this->getLabel();

        return [
            'layout' => $this->translatable_layout,
            'inputs' => array_values(array_map(
                fn (LocaleInput $input) => [
                    ...$input->toArray(),
                    'name' => static::toInputName($input->name),
                    'statePath' => $input->name,
                    'label' => $input->label($label),
                ],
                $this->getTranslatableInputs(),
            )),
        ];
    }

    /**
     * Expands rules keyed by the field's name into rules per input.
     *
     * @param  array<string, array<int, mixed>>  $rules
     * @return array<string, array<int, mixed>>
     */
    protected function translateRules(array $rules): array
    {
        if ($this->translatableResolver() === null) {
            return Translatable::rules($this->translatableModel(), $rules, $this->translatable_require_all);
        }

        $expanded = [];

        foreach ($rules as $path => $field_rules) {
            if ($path !== $this->getStatePath()) {
                $expanded[$path] = $field_rules;

                continue;
            }

            foreach ($this->getTranslatableInputs() as $input) {
                $expanded[$input->name] = $input->is_primary || $this->translatable_require_all
                    ? $field_rules
                    : ['nullable', ...array_values(array_filter($field_rules, fn ($rule) => $rule !== 'required' && $rule !== 'nullable'))];
            }
        }

        return $expanded;
    }

    /**
     * The values of the inputs from the record, keyed by input name.
     *
     * @return array<string, mixed>
     */
    protected function translatableValues(mixed $record, mixed $fallback): array
    {
        $inputs = $this->getTranslatableInputs();

        if ($inputs === []) {
            return [];
        }

        $values = $record instanceof Model && $this->translatableResolver() === null
            ? Translatable::values($record, [$this->getStatePath()])
            : [];

        $state = [];

        foreach ($inputs as $input) {
            $value = array_key_exists($input->name, $values) ? $values[$input->name] : data_get($record, $input->name);

            $state[$input->name] = $value === null && $input->is_primary ? $fallback : $value;
        }

        return $state;
    }
}

<?php

namespace Javaabu\Stencil\Components\Concerns;

use Javaabu\Stencil\Conditions\Condition;
use Javaabu\Stencil\Conditions\Effects;
use Javaabu\Stencil\Fields\Field;

/**
 * Declarative conditions on other fields, applied in the browser without a
 * round trip, and on the server for validation and saving.
 */
trait HasConditions
{
    /** @var array<int, Condition> */
    protected array $conditions = [];

    /**
     * Show the component while another field has one of the values.
     */
    public function visibleWhen(string $field, mixed $values): static
    {
        return $this->condition(Condition::value(Effects::VISIBLE, $field, $values));
    }

    /**
     * Hide the component while another field has one of the values.
     */
    public function hiddenWhen(string $field, mixed $values): static
    {
        return $this->condition(Condition::value(Effects::HIDDEN, $field, $values));
    }

    /**
     * Enable the fields inside while another field has one of the values; disable them otherwise.
     */
    public function enabledWhen(string $field, mixed $values): static
    {
        return $this->condition(Condition::value(Effects::ENABLED, $field, $values));
    }

    /**
     * Disable the fields inside while another field has one of the values.
     */
    public function disabledWhen(string $field, mixed $values): static
    {
        return $this->condition(Condition::value(Effects::DISABLED, $field, $values));
    }

    public function visibleWhenChecked(string $checkbox): static
    {
        return $this->condition(Condition::checked(Effects::VISIBLE, $checkbox));
    }

    public function hiddenWhenChecked(string $checkbox): static
    {
        return $this->condition(Condition::checked(Effects::HIDDEN, $checkbox));
    }

    public function enabledWhenChecked(string $checkbox): static
    {
        return $this->condition(Condition::checked(Effects::ENABLED, $checkbox));
    }

    public function disabledWhenChecked(string $checkbox): static
    {
        return $this->condition(Condition::checked(Effects::DISABLED, $checkbox));
    }

    public function condition(Condition $condition): static
    {
        $this->conditions[] = $condition;

        return $this;
    }

    /**
     * @return array<int, Condition>
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * Whether a condition on this component or a component around it hides it,
     * given the current state.
     */
    public function isHiddenByConditions(): bool
    {
        foreach ($this->conditions as $condition) {
            if ($condition->hides($this->getForm())) {
                return true;
            }
        }

        return $this->parent?->isHiddenByConditions() ?? false;
    }

    /**
     * Whether a condition on this component or a component around it disables
     * (or hides) it, given the current state.
     */
    public function isDisabledByConditions(): bool
    {
        foreach ($this->conditions as $condition) {
            if ($condition->disables($this->getForm())) {
                return true;
            }
        }

        return $this->parent?->isDisabledByConditions() ?? false;
    }

    /**
     * The conditions as drivers get them, with the selector of the watched field.
     *
     * @return array<int, array{
     *     effect: string,
     *     field: string,
     *     selector: string,
     *     values: array<int, string>|null,
     *     checkbox: bool
     * }>
     */
    public function getConditionsForDriver(): array
    {
        return array_map(fn (Condition $condition) => [
            'effect' => $condition->effect->value,
            'field' => $condition->field,
            'selector' => $this->conditionSelector($condition->field),
            'values' => $condition->values,
            'checkbox' => $condition->watchesCheckbox(),
        ], $this->conditions);
    }

    protected function conditionSelector(string $field): string
    {
        $target = $this->getForm()->getField($field);

        if ($target instanceof Field) {
            return $target->getSelector();
        }

        return '#' . str_replace('.', '-', $field);
    }
}

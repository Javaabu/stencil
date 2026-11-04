<?php

namespace Javaabu\Stencil\Conditions;

use BackedEnum;
use Illuminate\Contracts\Support\Arrayable;
use Javaabu\Stencil\Form;

/**
 * A declarative condition on another field's value, checked in the browser
 * without a round trip (forms' conditional-wrapper) and on the server for
 * validation and dehydration.
 */
class Condition
{
    /**
     * @param  array<int, string>|null  $values  null when watching a checkbox
     */
    public function __construct(
        public readonly Effects $effect,
        public readonly string $field,
        public readonly ?array $values = null,
    ) {}

    /**
     * @param  mixed  $values  a value, an array of values, an enum or a collection
     */
    public static function value(Effects $effect, string $field, mixed $values): static
    {
        return new static($effect, static::normalizeField($field), static::normalizeValues($values));
    }

    public static function checked(Effects $effect, string $field): static
    {
        return new static($effect, static::normalizeField($field));
    }

    public function watchesCheckbox(): bool
    {
        return $this->values === null;
    }

    /**
     * Whether the watched field matches: has one of the values, or is checked.
     */
    public function matches(Form $form): bool
    {
        $state = $form->getStateValue($this->field);

        if ($this->watchesCheckbox()) {
            return static::isChecked($state);
        }

        $current = array_map(static::stringify(...), is_array($state) ? $state : [$state]);

        return array_intersect($current, $this->values) !== [];
    }

    /**
     * Whether the condition hides the component, given the current state.
     */
    public function hides(Form $form): bool
    {
        return match ($this->effect) {
            Effects::VISIBLE => ! $this->matches($form),
            Effects::HIDDEN => $this->matches($form),
            default => false,
        };
    }

    /**
     * Whether the condition disables the component, given the current state.
     * Hiding conditions disable too, as hidden fields aren't submitted.
     */
    public function disables(Form $form): bool
    {
        return match ($this->effect) {
            Effects::ENABLED, Effects::VISIBLE => ! $this->matches($form),
            Effects::DISABLED, Effects::HIDDEN => $this->matches($form),
        };
    }

    protected static function isChecked(mixed $state): bool
    {
        if (is_array($state)) {
            return $state !== [];
        }

        if (is_string($state)) {
            return ! in_array($state, ['', '0', 'false', 'off'], true);
        }

        return (bool) $state;
    }

    protected static function normalizeField(string $field): string
    {
        return str_replace(['[]', '[', ']'], ['', '.', ''], $field);
    }

    /**
     * @return array<int, string>
     */
    protected static function normalizeValues(mixed $values): array
    {
        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        return array_values(array_map(static::stringify(...), is_array($values) ? $values : [$values]));
    }

    protected static function stringify(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }
}

<?php

namespace Javaabu\Stencil\Fields;

use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Javaabu\Stencil\Fields\Concerns\CanBeUnique;
use Javaabu\Stencil\Fields\Concerns\HasLength;
use Javaabu\Stencil\Fields\Concerns\HasThaana;

/**
 * A one-line input: text, email, password, number, tel or url.
 */
class TextInput extends Field
{
    use CanBeUnique;
    use HasLength;
    use HasThaana;

    protected string $type = 'text';

    protected string $input_type = 'text';

    protected bool $is_integer = false;

    protected int|float|Closure|null $min_value = null;

    protected int|float|Closure|null $max_value = null;

    protected int|float|string|Closure|null $step = null;

    protected int|Closure|null $decimals = null;

    protected string|Htmlable|Closure|null $prefix = null;

    protected string|Htmlable|Closure|null $suffix = null;

    protected string|Closure|null $autocomplete = null;

    protected function setUp(): void
    {
        $this->component = 'text';
    }

    protected function inputType(string $type, ?string $component = null): static
    {
        $this->input_type = $type;
        $this->component = $component ?? $type;

        return $this;
    }

    public function email(): static
    {
        return $this->inputType('email');
    }

    /**
     * A password input. Its value is never filled from the record.
     */
    public function password(): static
    {
        return $this->inputType('password');
    }

    public function tel(): static
    {
        return $this->inputType('tel');
    }

    public function url(): static
    {
        return $this->inputType('url');
    }

    public function numeric(): static
    {
        return $this->inputType('number');
    }

    public function integer(): static
    {
        $this->is_integer = true;

        return $this->numeric()->step(1);
    }

    public function getInputType(): string
    {
        return $this->input_type;
    }

    /**
     * Whether integer() was called: the value is validated and saved as an int.
     */
    public function isInteger(): bool
    {
        return $this->is_integer;
    }

    public function isNumeric(): bool
    {
        return $this->input_type === 'number';
    }

    public function minValue(int|float|Closure|null $value): static
    {
        $this->min_value = $value;

        return $this;
    }

    public function maxValue(int|float|Closure|null $value): static
    {
        $this->max_value = $value;

        return $this;
    }

    public function step(int|float|string|Closure|null $step): static
    {
        $this->step = $step;

        return $this;
    }

    /**
     * Round number inputs to this many decimals as the user types.
     */
    public function decimals(int|Closure|null $decimals): static
    {
        $this->decimals = $decimals;

        return $this;
    }

    /**
     * Text or HTML before the input (the prepend slot).
     */
    public function prefix(string|Htmlable|Closure|null $prefix): static
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Text or HTML after the input (the append slot).
     */
    public function suffix(string|Htmlable|Closure|null $suffix): static
    {
        $this->suffix = $suffix;

        return $this;
    }

    public function autocomplete(string|Closure|null $autocomplete): static
    {
        $this->autocomplete = $autocomplete;

        return $this;
    }

    protected function readFromRecord(mixed $record): mixed
    {
        if ($this->input_type === 'password') {
            return null;
        }

        return parent::readFromRecord($record);
    }

    protected function getTypeRules(): array
    {
        $rules = match ($this->input_type) {
            'email' => ['string', 'email'],
            'url' => ['string', 'url'],
            'number' => [$this->is_integer ? 'integer' : 'numeric'],
            default => ['string'],
        };

        if ($this->isNumeric()) {
            $min = $this->evaluate($this->min_value);
            $max = $this->evaluate($this->max_value);

            if ($min !== null) {
                $rules[] = "min:{$min}";
            }

            if ($max !== null) {
                $rules[] = "max:{$max}";
            }

            return [...$rules, ...$this->getUniqueRules()];
        }

        return [...$rules, ...$this->getLengthRules(), ...$this->getUniqueRules()];
    }

    protected function getFieldProps(): array
    {
        $props = [
            'thaana' => $this->isThaana() && ! in_array($this->input_type, ['email', 'password', 'url', 'tel', 'number']) ?: null,
            'decimals' => $this->evaluate($this->decimals),
            'prepend' => $this->evaluate($this->prefix),
            'append' => $this->evaluate($this->suffix),
            'autoComplete' => $this->evaluate($this->autocomplete),
        ];

        if ($this->isNumeric()) {
            return [
                ...$props,
                'min' => $this->evaluate($this->min_value),
                'max' => $this->evaluate($this->max_value),
                'step' => $this->evaluate($this->step),
            ];
        }

        return [...$props, ...$this->getLengthProps()];
    }

    protected function castDehydratedState(mixed $state): mixed
    {
        $state = parent::castDehydratedState($state);

        if ($state === null || ! $this->isNumeric() || ! is_numeric($state)) {
            return $state;
        }

        return $this->is_integer ? (int) $state : $state + 0;
    }
}

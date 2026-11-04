<?php

namespace Javaabu\Stencil\Components;

use BackedEnum;
use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Javaabu\Stencil\Fields\Field;

/**
 * A read-only value with its label: <x-forms::text-entry> / <TextEntry>.
 * Shows the form's state at its name (or the record's attribute), or value().
 */
class TextEntry extends Component
{
    protected string $type = 'text-entry';

    protected string $name;

    protected string|Closure|null $label = null;

    protected mixed $value = null;

    protected bool $has_value = false;

    protected bool|Closure $is_multiline = false;

    protected bool|Closure $is_inline = false;

    final public function __construct(string $name)
    {
        $this->name = Field::normalizeName($name);
    }

    public static function make(string $name): static
    {
        return app(static::class, ['name' => $name]);
    }

    public function getKey(): string
    {
        return $this->key ?? "entry-{$this->name}";
    }

    public function label(string|Closure|null $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * The value to show instead of the state: a value or fn ($record, Get $get) => ….
     */
    public function value(mixed $value): static
    {
        $this->value = $value;
        $this->has_value = true;

        return $this;
    }

    public function multiline(bool|Closure $condition = true): static
    {
        $this->is_multiline = $condition;

        return $this;
    }

    public function inline(bool|Closure $condition = true): static
    {
        $this->is_inline = $condition;

        return $this;
    }

    public function getValue(): mixed
    {
        if ($this->has_value) {
            $value = $this->evaluate($this->value);
        } else {
            $form = $this->getForm();

            $value = $form->hasField($this->name)
                ? $form->getStateValue($this->name)
                : data_get($form->getRecord(), $this->name);
        }

        if ($value instanceof BackedEnum && method_exists($value, 'getLabel')) {
            return $value->getLabel();
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof Htmlable) {
            return $value->toHtml();
        }

        return $value;
    }

    public function getProps(): array
    {
        $label = $this->evaluate($this->label)
            ?? trans(Str::of(Str::afterLast($this->name, '.'))->camel()->snake()->replace('_', ' ')->title()->toString());

        return array_filter([
            'name' => str_replace('.', '-', $this->name),
            'label' => $label,
            'value' => $this->getValue(),
            'multiline' => $this->evaluate($this->is_multiline) ?: null,
            'inline' => $this->evaluate($this->is_inline) ?: null,
        ], fn ($value) => $value !== null);
    }
}

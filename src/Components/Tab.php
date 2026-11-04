<?php

namespace Javaabu\Stencil\Components;

use Closure;
use Illuminate\Support\Str;

/**
 * A tab in Tabs.
 */
class Tab extends Component
{
    protected string $type = 'tab';

    protected string|Closure $label;

    protected string|Closure|null $icon = null;

    final public function __construct() {}

    /**
     * @param  array<int, Component|null>|Closure  $schema
     */
    public static function make(string|Closure $label, array|Closure $schema = []): static
    {
        return app(static::class)->label($label)->schema($schema);
    }

    public function label(string|Closure $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return (string) $this->evaluate($this->label);
    }

    public function icon(string|Closure|null $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * The tab's key: given with key(), or from its label when the label is a plain string.
     */
    public function getKey(): string
    {
        if ($this->key !== null) {
            return $this->key;
        }

        if (is_string($this->label) && Str::slug($this->label) !== '') {
            return $this->getType() . '-' . Str::slug($this->label, '_');
        }

        return parent::getKey();
    }

    public function getProps(): array
    {
        return array_filter([
            'label' => $this->getLabel(),
            'icon' => $this->evaluate($this->icon),
            'columns' => $this->getColumns(),
        ], fn ($value) => $value !== null);
    }
}

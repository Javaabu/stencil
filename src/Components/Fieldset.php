<?php

namespace Javaabu\Stencil\Components;

use Closure;
use Illuminate\Contracts\Support\Htmlable;

/**
 * A <fieldset> with a <legend>.
 */
class Fieldset extends Component
{
    protected string $type = 'fieldset';

    protected string|Htmlable|Closure|null $label = null;

    final public function __construct() {}

    /**
     * @param  array<int, Component|null>|Closure  $schema
     */
    public static function make(string|Htmlable|Closure|null $label = null, array|Closure $schema = []): static
    {
        return app(static::class)->label($label)->schema($schema);
    }

    public function label(string|Htmlable|Closure|null $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getProps(): array
    {
        return array_filter([
            'label' => $this->evaluate($this->label),
            'columns' => $this->getColumns(),
        ], fn ($value) => $value !== null);
    }
}

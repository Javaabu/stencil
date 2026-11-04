<?php

namespace Javaabu\Stencil\Components;

use Closure;
use Illuminate\Contracts\Support\Htmlable;

/**
 * A card with a heading and an optional description: <x-forms::card> / <Card>.
 */
class Section extends Component
{
    protected string $type = 'section';

    protected string|Htmlable|Closure|null $heading = null;

    protected string|Htmlable|Closure|null $description = null;

    final public function __construct() {}

    /**
     * @param  array<int, Component|null>|Closure  $schema
     */
    public static function make(string|Htmlable|Closure|null $heading = null, array|Closure $schema = []): static
    {
        return app(static::class)->heading($heading)->schema($schema);
    }

    public function heading(string|Htmlable|Closure|null $heading): static
    {
        $this->heading = $heading;

        return $this;
    }

    public function description(string|Htmlable|Closure|null $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getProps(): array
    {
        return array_filter([
            'heading' => $this->evaluate($this->heading),
            'description' => $this->evaluate($this->description),
            'columns' => $this->getColumns(),
        ], fn ($value) => $value !== null);
    }
}

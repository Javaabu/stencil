<?php

namespace Javaabu\Stencil\Components;

use Closure;

/**
 * Components side by side in columns, with no markup of its own.
 */
class Grid extends Component
{
    protected string $type = 'grid';

    final public function __construct() {}

    /**
     * @param  int|array<string, int>|Closure  $columns
     * @param  array<int, Component|null>|Closure  $schema
     */
    public static function make(int|array|Closure $columns = 2, array|Closure $schema = []): static
    {
        return app(static::class)->columns($columns)->schema($schema);
    }

    public function getProps(): array
    {
        return array_filter([
            'columns' => $this->getColumns(),
        ], fn ($value) => $value !== null);
    }
}

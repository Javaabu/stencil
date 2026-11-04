<?php

namespace Javaabu\Stencil\Components;

use Closure;

/**
 * Components grouped without markup: to show, hide or span them together.
 */
class Group extends Component
{
    protected string $type = 'group';

    final public function __construct() {}

    /**
     * @param  array<int, Component|null>|Closure  $schema
     */
    public static function make(array|Closure $schema = []): static
    {
        return app(static::class)->schema($schema);
    }

    public function getProps(): array
    {
        return array_filter([
            'columns' => $this->getColumns(),
        ], fn ($value) => $value !== null);
    }
}

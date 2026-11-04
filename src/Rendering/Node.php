<?php

namespace Javaabu\Stencil\Rendering;

use Javaabu\Stencil\Components\Component;

/**
 * A component resolved for a driver: closures evaluated against the current
 * state, hidden components marked, props ready to render. Drivers turn a tree
 * of nodes into output; they never evaluate the schema themselves.
 */
class Node
{
    /**
     * @param  string  $type  what to render: a Blade view stencil::components.{type}, a React component in the registry
     * @param  string  $key  unique in the form; the field name for fields
     * @param  array<string, mixed>  $props  camelCase props; for fields, the javaabu/forms ones
     * @param  array<int, Node>  $children
     * @param  bool  $hidden  hidden on the server: render nothing (or an empty placeholder)
     * @param  string|null  $name  the field's input name, in bracket notation
     * @param  string|null  $component  the javaabu/forms component a field renders as (text, select2, file-upload)
     * @param  array<int, array<string, mixed>>  $conditions  conditions checked in the browser
     * @param  array{debounce: int|null, onBlur: bool}|null  $live  re-evaluate the form when this field changes
     * @param  array<string, int|string>|null  $span  grid columns taken, per breakpoint
     * @param  string|null  $view  a Blade view replacing the default one
     * @param  array<string, mixed>  $meta  extra data for custom drivers and components
     */
    public function __construct(
        public string $type,
        public string $key,
        public array $props = [],
        public array $children = [],
        public bool $hidden = false,
        public ?string $name = null,
        public ?string $component = null,
        public array $conditions = [],
        public ?array $live = null,
        public ?array $span = null,
        public ?string $view = null,
        public array $meta = [],
        public ?Component $source = null,
    ) {}

    public function prop(string $key, mixed $default = null): mixed
    {
        return $this->props[$key] ?? $default;
    }

    public function isField(): bool
    {
        return $this->name !== null;
    }

    /**
     * The visible children.
     *
     * @return array<int, Node>
     */
    public function visibleChildren(): array
    {
        return array_values(array_filter($this->children, fn (Node $child) => ! $child->hidden));
    }

    /**
     * This node and every node inside it, depth first.
     *
     * @return iterable<int, Node>
     */
    public function descendants(): iterable
    {
        foreach ($this->children as $child) {
            yield $child;

            yield from $child->descendants();
        }
    }

    public function find(string $key): ?Node
    {
        foreach ($this->descendants() as $node) {
            if ($node->key === $key) {
                return $node;
            }
        }

        return null;
    }
}

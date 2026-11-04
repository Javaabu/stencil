<?php

namespace Javaabu\Stencil\Components\Concerns;

use Closure;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Fields\Field;

trait HasChildren
{
    /** @var array<int, Component|null>|Closure */
    protected array|Closure $child_components = [];

    /** @var array<int, Component>|null */
    protected ?array $resolved_children = null;

    /** @var array<int, int> */
    protected array $index_path = [];

    /** @var int|array<string, int>|Closure|null */
    protected int|array|Closure|null $columns = null;

    /**
     * The components inside this one. Null entries are skipped, so
     * `$condition ? TextInput::make('x') : null` works.
     *
     * @param  array<int, Component|null>|Closure  $components
     */
    public function schema(array|Closure $components): static
    {
        $this->child_components = $components;
        $this->resolved_children = null;

        return $this;
    }

    /**
     * @return array<int, Component>
     */
    public function getChildComponents(): array
    {
        if ($this->resolved_children !== null) {
            return $this->resolved_children;
        }

        $components = array_values(array_filter($this->evaluate($this->resolveChildComponents())));

        foreach ($components as $index => $component) {
            $component->parent($this);
            $component->index_path = [...$this->index_path, $index];
            $component->generatedKey($component->getType() . '-' . implode('-', $component->index_path));
        }

        return $this->resolved_children = $components;
    }

    /**
     * @return array<int, Component|null>|Closure
     */
    protected function resolveChildComponents(): array|Closure
    {
        return $this->child_components;
    }

    public function hasChildComponents(): bool
    {
        return $this->getChildComponents() !== [];
    }

    /**
     * Every field inside this component, at any depth.
     *
     * @return array<int, Field>
     */
    public function getFlatFields(): array
    {
        $fields = [];

        foreach ($this->getChildComponents() as $component) {
            if ($component instanceof Field) {
                $fields[] = $component;
            }

            array_push($fields, ...$component->getFlatFields());
        }

        return $fields;
    }

    /**
     * Lay the components inside out in a grid: columns(2) is one column on
     * small screens and two from the md breakpoint; columns(['sm' => 2, 'xl' => 4])
     * sets each breakpoint (default, sm, md, lg, xl).
     *
     * @param  int|array<string, int>|Closure|null  $columns
     */
    public function columns(int|array|Closure|null $columns = 2): static
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * @return array<string, int>|null
     */
    public function getColumns(): ?array
    {
        $columns = $this->evaluate($this->columns);

        if ($columns === null) {
            return null;
        }

        if (is_int($columns)) {
            return $columns > 1 ? ['default' => 1, 'md' => $columns] : null;
        }

        return ['default' => 1, ...array_map('intval', $columns)];
    }
}

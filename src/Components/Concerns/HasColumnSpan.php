<?php

namespace Javaabu\Stencil\Components\Concerns;

use Closure;

trait HasColumnSpan
{
    /** @var int|string|array<string, int|string>|Closure|null */
    protected int|string|array|Closure|null $column_span = null;

    /**
     * How many grid columns the component takes, from the md breakpoint: columnSpan(2),
     * columnSpan('full'), or per breakpoint: columnSpan(['md' => 2, 'xl' => 1]).
     *
     * @param  int|string|array<string, int|string>|Closure|null  $span
     */
    public function columnSpan(int|string|array|Closure|null $span): static
    {
        $this->column_span = $span;

        return $this;
    }

    public function columnSpanFull(): static
    {
        return $this->columnSpan('full');
    }

    /**
     * @return array<string, int|string>|null
     */
    public function getColumnSpan(): ?array
    {
        $span = $this->evaluate($this->column_span);

        if ($span === null) {
            return null;
        }

        return is_array($span) ? $span : ['md' => $span];
    }
}

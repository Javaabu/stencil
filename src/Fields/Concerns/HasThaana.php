<?php

namespace Javaabu\Stencil\Fields\Concerns;

use Closure;

trait HasThaana
{
    protected bool|Closure $is_thaana = false;

    /**
     * Type Thaana from a Latin keyboard, right to left. For fields that take Dhivehi.
     */
    public function thaana(bool|Closure $condition = true): static
    {
        $this->is_thaana = $condition;

        return $this;
    }

    public function isThaana(): bool
    {
        return (bool) $this->evaluate($this->is_thaana);
    }
}

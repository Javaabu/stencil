<?php

namespace Javaabu\Stencil\Components\Concerns;

use Closure;
use Illuminate\Support\Arr;

trait CanBeHidden
{
    protected bool|Closure $is_hidden = false;

    protected bool|Closure $is_visible = true;

    /** @var array<int, string> */
    protected array $hidden_on = [];

    /** @var array<int, string> */
    protected array $visible_on = [];

    /**
     * Hide the component, and leave its fields out of validation and saving.
     */
    public function hidden(bool|Closure $condition = true): static
    {
        $this->is_hidden = $condition;

        return $this;
    }

    public function visible(bool|Closure $condition = true): static
    {
        $this->is_visible = $condition;

        return $this;
    }

    /**
     * Hide the component for an operation: hiddenOn('edit'), hiddenOn(['create', 'view']).
     *
     * @param  string|array<int, string>  $operations
     */
    public function hiddenOn(string|array $operations): static
    {
        $this->hidden_on = Arr::wrap($operations);

        return $this;
    }

    /**
     * Show the component only for an operation: visibleOn('create').
     *
     * @param  string|array<int, string>  $operations
     */
    public function visibleOn(string|array $operations): static
    {
        $this->visible_on = Arr::wrap($operations);

        return $this;
    }

    /**
     * Whether the component is hidden on the server: by hidden(), visible(), the
     * operation, or a hidden parent. Hidden components aren't rendered.
     *
     * Conditions (visibleWhen(), …) don't hide it here: it's rendered and the
     * browser shows or hides it. They count for validation and saving; see
     * isDisabledByConditions().
     */
    public function isHidden(): bool
    {
        if ($this->parent?->isHidden()) {
            return true;
        }

        return $this->isHiddenItself();
    }

    protected function isHiddenItself(): bool
    {
        if ($this->evaluate($this->is_hidden)) {
            return true;
        }

        if (! $this->evaluate($this->is_visible)) {
            return true;
        }

        if ($this->hidden_on || $this->visible_on) {
            $operation = $this->getForm()->getOperation();

            if (in_array($operation, $this->hidden_on)) {
                return true;
            }

            if ($this->visible_on && ! in_array($operation, $this->visible_on)) {
                return true;
            }
        }

        return false;
    }

    public function isVisible(): bool
    {
        return ! $this->isHidden();
    }
}

<?php

namespace Javaabu\Stencil\Components;

use Closure;
use Illuminate\Contracts\Support\Htmlable;

/**
 * A step in a Wizard.
 */
class Step extends Tab
{
    protected string $type = 'step';

    protected string|Htmlable|Closure|null $description = null;

    public function description(string|Htmlable|Closure|null $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getProps(): array
    {
        return array_filter([
            ...parent::getProps(),
            'description' => $this->evaluate($this->description),
        ], fn ($value) => $value !== null);
    }
}

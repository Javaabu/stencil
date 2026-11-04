<?php

namespace Javaabu\Stencil\Components\Concerns;

use Closure;

trait HasExtraAttributes
{
    /** @var array<int, array<string, mixed>|Closure> */
    protected array $extra_attributes = [];

    /**
     * Attributes or props passed through to the rendered component: HTML attributes
     * (class, data-*), or javaabu/forms attributes Stencil has no method for
     * (['ajax-url' => …] in Blade, ajaxUrl in React; either spelling works).
     *
     * @param  array<string, mixed>|Closure  $attributes
     */
    public function extraAttributes(array|Closure $attributes, bool $merge = true): static
    {
        $this->extra_attributes = $merge ? [...$this->extra_attributes, $attributes] : [$attributes];

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraAttributes(): array
    {
        $attributes = [];

        foreach ($this->extra_attributes as $set) {
            $attributes = array_merge($attributes, $this->evaluate($set) ?? []);
        }

        return $attributes;
    }
}

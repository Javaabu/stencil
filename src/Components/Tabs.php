<?php

namespace Javaabu\Stencil\Components;

use Closure;

/**
 * Tabs of components, one shown at a time: <x-forms::tabs> / <Tabs>.
 * A tab with an invalid field opens when the form shows errors.
 */
class Tabs extends Component
{
    protected string $type = 'tabs';

    protected string|Closure|null $active = null;

    final public function __construct() {}

    /**
     * @param  array<int, Tab|null>|Closure  $tabs
     */
    public static function make(array|Closure $tabs = []): static
    {
        return app(static::class)->tabs($tabs);
    }

    /**
     * @param  array<int, Tab|null>|Closure  $tabs
     */
    public function tabs(array|Closure $tabs): static
    {
        return $this->schema($tabs);
    }

    /**
     * The tab open at first, by key. Defaults to the first visible one.
     */
    public function activeTab(string|Closure|null $key): static
    {
        $this->active = $key;

        return $this;
    }

    public function getProps(): array
    {
        $tabs = array_filter($this->getChildComponents(), fn (Component $tab) => ! $tab->isHidden());

        return array_filter([
            'active' => $this->evaluate($this->active) ?? (reset($tabs) ?: null)?->getKey(),
        ], fn ($value) => $value !== null);
    }
}

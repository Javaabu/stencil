<?php

namespace Javaabu\Stencil\Components;

use Closure;

/**
 * Steps shown one at a time with Next and Back buttons. The form is submitted
 * from the last step; a step with an invalid field opens when errors come back.
 */
class Wizard extends Component
{
    protected string $type = 'wizard';

    protected int|Closure $start_step = 1;

    final public function __construct() {}

    /**
     * @param  array<int, Step|null>|Closure  $steps
     */
    public static function make(array|Closure $steps = []): static
    {
        return app(static::class)->steps($steps);
    }

    /**
     * @param  array<int, Step|null>|Closure  $steps
     */
    public function steps(array|Closure $steps): static
    {
        return $this->schema($steps);
    }

    /**
     * The step shown first, counting from 1.
     */
    public function startOnStep(int|Closure $step): static
    {
        $this->start_step = $step;

        return $this;
    }

    public function getProps(): array
    {
        return [
            'startStep' => (int) $this->evaluate($this->start_step),
            'nextLabel' => __('stencil::strings.next'),
            'previousLabel' => __('stencil::strings.previous'),
            'stepLabel' => __('stencil::strings.step'),
        ];
    }
}

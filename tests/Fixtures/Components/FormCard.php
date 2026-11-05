<?php

namespace Javaabu\Stencil\Tests\Fixtures\Components;

use Closure;
use Illuminate\View\Component;

/**
 * A class component that takes the form as an attribute, not a constructor
 * parameter: Blade sanitizes such attributes, casting Stringable objects.
 */
class FormCard extends Component
{
    public function __construct(
        public string $title = '',
    ) {}

    public function render(): Closure
    {
        return fn (array $data) => view('fixtures::form-card', $data)->render();
    }
}

<?php

namespace Javaabu\Stencil\Tests\Fixtures\Fields;

use Closure;
use Javaabu\Stencil\Fields\Field;

class Rating extends Field
{
    protected string $type = 'rating';

    protected ?string $view = 'fixtures::rating';

    protected int|Closure $max = 5;

    public function max(int|Closure $max): static
    {
        $this->max = $max;

        return $this;
    }

    protected function getTypeRules(): array
    {
        return ['integer', 'min:1', "max:{$this->evaluate($this->max)}"];
    }

    protected function getFieldProps(): array
    {
        return ['max' => $this->evaluate($this->max)];
    }
}

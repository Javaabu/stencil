<?php

namespace Javaabu\Stencil\Fields;

use Closure;
use Javaabu\Stencil\Fields\Concerns\HasLength;
use Javaabu\Stencil\Fields\Concerns\HasThaana;

class Textarea extends Field
{
    use HasLength;
    use HasThaana;

    protected string $type = 'textarea';

    protected ?string $component = 'textarea';

    protected int|Closure|null $rows = null;

    protected bool|Closure $auto_resize = false;

    public function rows(int|Closure|null $rows): static
    {
        $this->rows = $rows;

        return $this;
    }

    /**
     * Grow the textarea with its content.
     */
    public function autosize(bool|Closure $condition = true): static
    {
        $this->auto_resize = $condition;

        return $this;
    }

    protected function getTypeRules(): array
    {
        return ['string', ...$this->getLengthRules()];
    }

    protected function getFieldProps(): array
    {
        return [
            'rows' => $this->evaluate($this->rows),
            'autoResize' => $this->evaluate($this->auto_resize) ?: null,
            'thaana' => $this->isThaana() ?: null,
            ...$this->getLengthProps(),
        ];
    }
}

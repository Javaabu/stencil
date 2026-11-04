<?php

namespace Javaabu\Stencil\Fields;

use Closure;
use Javaabu\Stencil\Fields\Concerns\HasLength;

/**
 * A rich text editor: TinyMCE in Blade, Onu's editor in React.
 */
class Wysiwyg extends Field
{
    use HasLength;

    protected string $type = 'wysiwyg';

    protected ?string $component = 'wysiwyg';

    protected int|Closure|null $rows = null;

    public function rows(int|Closure|null $rows): static
    {
        $this->rows = $rows;

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
        ];
    }
}

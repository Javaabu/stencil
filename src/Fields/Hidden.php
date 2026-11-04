<?php

namespace Javaabu\Stencil\Fields;

class Hidden extends Field
{
    protected string $type = 'hidden';

    protected ?string $component = 'hidden';

    public function getProps(): array
    {
        return array_filter([
            'disabled' => $this->isDisabled(with_conditions: false) ?: null,
        ]);
    }
}

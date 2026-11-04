<?php

namespace Javaabu\Stencil\Fields;

/**
 * A time picker. The state is an H:i string.
 */
class TimePicker extends DatePicker
{
    protected string $type = 'time';

    protected ?string $component = 'time';

    protected string $format = 'H:i';

    protected function getTypeRules(): array
    {
        return ["date_format:{$this->format}"];
    }

    protected function getFieldProps(): array
    {
        return [];
    }
}

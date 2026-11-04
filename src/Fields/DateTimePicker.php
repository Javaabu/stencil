<?php

namespace Javaabu\Stencil\Fields;

/**
 * A date and time picker. The state is a Y-m-d H:i string.
 */
class DateTimePicker extends DatePicker
{
    protected string $type = 'datetime';

    protected ?string $component = 'datetime';

    protected string $format = 'Y-m-d H:i';
}

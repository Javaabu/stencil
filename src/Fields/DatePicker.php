<?php

namespace Javaabu\Stencil\Fields;

use Carbon\CarbonInterface;
use Closure;
use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * A date picker. The state is a Y-m-d string.
 */
class DatePicker extends Field
{
    protected string $type = 'date';

    protected ?string $component = 'date';

    /**
     * The format of the state and the submitted value.
     */
    protected string $format = 'Y-m-d';

    protected DateTimeInterface|string|Closure|null $min_date = null;

    protected DateTimeInterface|string|Closure|null $max_date = null;

    public function minDate(DateTimeInterface|string|Closure|null $date): static
    {
        $this->min_date = $date;

        return $this;
    }

    public function maxDate(DateTimeInterface|string|Closure|null $date): static
    {
        $this->max_date = $date;

        return $this;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function normalizeState(mixed $state): mixed
    {
        if ($state instanceof DateTimeInterface) {
            return Carbon::instance($state)->format($this->format);
        }

        return parent::normalizeState($state);
    }

    protected function formatDate(mixed $date): ?string
    {
        $date = $this->evaluate($date);

        if ($date === null) {
            return null;
        }

        if (! $date instanceof CarbonInterface) {
            $date = Carbon::parse($date);
        }

        return $date->format($this->format);
    }

    protected function getTypeRules(): array
    {
        $min = $this->formatDate($this->min_date);
        $max = $this->formatDate($this->max_date);

        return array_values(array_filter([
            'date',
            $min !== null ? "after_or_equal:{$min}" : null,
            $max !== null ? "before_or_equal:{$max}" : null,
        ]));
    }

    protected function getFieldProps(): array
    {
        return [
            'min' => $this->formatDate($this->min_date),
            'max' => $this->formatDate($this->max_date),
        ];
    }
}

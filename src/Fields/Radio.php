<?php

namespace Javaabu\Stencil\Fields;

use Closure;
use Javaabu\Stencil\Fields\Concerns\HasOptions;

/**
 * A group of radio buttons, one per option.
 */
class Radio extends Field
{
    use HasOptions;

    protected string $type = 'radio';

    protected ?string $component = 'radio';

    protected int|array|Closure|null $option_columns = null;

    /**
     * Lay the radio buttons out in columns.
     *
     * @param  int|array<string, int>|Closure|null  $columns
     */
    public function gridColumns(int|array|Closure|null $columns): static
    {
        $this->option_columns = $columns;

        return $this;
    }

    public function getSelector(): string
    {
        return "[name=\"{$this->getInputName()}\"]";
    }

    protected function readFromRecord(mixed $record): mixed
    {
        if ($this->relationship !== null) {
            return $this->readRelationshipState($record);
        }

        return parent::readFromRecord($record);
    }

    protected function getTypeRules(): array
    {
        return $this->getOptionRules();
    }

    protected function getFieldProps(): array
    {
        $columns = $this->evaluate($this->option_columns);

        return [
            'options' => $this->getOptions(),
            'columns' => is_int($columns) ? ['default' => 1, 'md' => $columns] : $columns,
        ];
    }
}

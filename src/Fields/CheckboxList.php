<?php

namespace Javaabu\Stencil\Fields;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Javaabu\Stencil\Contracts\SavesRelationships;
use Javaabu\Stencil\Fields\Concerns\HasOptions;

/**
 * A group of checkboxes for several values: channels[] = ['email', 'sms'].
 */
class CheckboxList extends Field implements SavesRelationships
{
    use HasOptions;

    protected string $type = 'checkbox-list';

    protected ?string $component = 'checkbox';

    protected int|array|Closure|null $option_columns = null;

    /**
     * Lay the checkboxes out in columns.
     *
     * @param  int|array<string, int>|Closure|null  $columns
     */
    public function gridColumns(int|array|Closure|null $columns): static
    {
        $this->option_columns = $columns;

        return $this;
    }

    protected function setUp(): void
    {
        $this->multiple();
    }

    public function getInputName(): string
    {
        return parent::getInputName() . '[]';
    }

    public function getSelector(): string
    {
        return "[name=\"{$this->getInputName()}\"]";
    }

    public function savesToAttribute(): bool
    {
        if ($this->relationship === null) {
            return true;
        }

        return ! $this->getRelationship() instanceof BelongsToMany;
    }

    public function saveRelationship(Model $record, mixed $state): void
    {
        if ($this->relationship === null) {
            return;
        }

        $relationship = $record->{$this->relationship}();

        if ($relationship instanceof BelongsToMany) {
            $relationship->sync($state ?? []);
        }
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
        return ['array'];
    }

    public function getAllValidationRules(): array
    {
        $rules = parent::getAllValidationRules();

        $item_rules = $this->getOptionRules();

        if ($item_rules) {
            $rules["{$this->getStatePath()}.*"] = $item_rules;
        }

        return $rules;
    }

    protected function castDehydratedState(mixed $state): mixed
    {
        return array_values(array_filter((array) ($state ?? []), fn ($value) => $value !== null && $value !== ''));
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

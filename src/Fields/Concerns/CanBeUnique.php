<?php

namespace Javaabu\Stencil\Fields\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

trait CanBeUnique
{
    protected ?array $unique = null;

    /**
     * The value must be unique in a table: unique() checks the form's model's table
     * and the field's column, ignoring the record being edited.
     */
    public function unique(?string $table = null, ?string $column = null, bool $ignore_record = true): static
    {
        $this->unique = [$table, $column, $ignore_record];

        return $this;
    }

    /**
     * @return array<int, Unique>
     */
    protected function getUniqueRules(): array
    {
        if ($this->unique === null) {
            return [];
        }

        [$table, $column, $ignore_record] = $this->unique;

        $form = $this->getForm();
        $model = $table === null ? $form->getModelInstance() : null;

        $rule = Rule::unique($table ?? $model::class, $column ?? $this->getStatePath());

        $record = $form->getRecord();

        if ($ignore_record && $record instanceof Model && $record->exists) {
            $rule->ignore($record);
        }

        return [$rule];
    }
}

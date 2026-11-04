<?php

namespace Javaabu\Stencil\Fields;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Javaabu\Stencil\Contracts\SavesRelationships;
use Javaabu\Stencil\Fields\Concerns\HasOptions;

/**
 * A select: native, or searchable (select2) with searchable().
 */
class Select extends Field implements SavesRelationships
{
    use HasOptions;

    protected string $type = 'select';

    protected ?string $component = 'select';

    protected bool|Closure $is_searchable = false;

    protected bool|Closure $allows_clear = true;

    protected bool|Closure $has_tags = false;

    protected string|Closure|null $ajax_url = null;

    protected ?array $ajax_fields = null;

    /**
     * A searchable select (<x-forms::select2> / <Select2>).
     */
    public function searchable(bool|Closure $condition = true): static
    {
        $this->is_searchable = $condition;

        return $this;
    }

    public function isSearchable(): bool
    {
        if ($this->evaluate($this->ajax_url) !== null) {
            return true;
        }

        return (bool) $this->evaluate($this->is_searchable);
    }

    /**
     * Show a button that clears a searchable select.
     */
    public function clearable(bool|Closure $condition = true): static
    {
        $this->allows_clear = $condition;

        return $this;
    }

    /**
     * Let users add values that aren't options (a searchable select of tags).
     */
    public function tags(bool|Closure $condition = true): static
    {
        $this->has_tags = $condition;

        return $this->allowAnyOption($condition)->searchable();
    }

    /**
     * Search options on the server: the URL returns JSON options, with the label
     * and value in name_field and id_field.
     */
    public function ajax(string|Closure $url, string $name_field = 'name', string $id_field = 'id'): static
    {
        $this->ajax_url = $url;
        $this->ajax_fields = [$name_field, $id_field];

        return $this->allowAnyOption();
    }

    public function getComponent(): ?string
    {
        return $this->isSearchable() ? 'select2' : 'select';
    }

    public function getInputName(): string
    {
        return parent::getInputName() . ($this->isMultiple() ? '[]' : '');
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

        if (! $relationship instanceof BelongsToMany) {
            return;
        }

        $relationship->sync($state ?? []);
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
        if ($this->isMultiple()) {
            return ['array'];
        }

        return $this->getOptionRules();
    }

    public function getAllValidationRules(): array
    {
        $rules = parent::getAllValidationRules();

        if (! $this->isMultiple()) {
            return $rules;
        }

        $item_rules = $this->getOptionRules();

        if ($item_rules) {
            $rules["{$this->getStatePath()}.*"] = $item_rules;
        }

        return $rules;
    }

    protected function castDehydratedState(mixed $state): mixed
    {
        if ($this->isMultiple()) {
            return array_values(array_filter((array) ($state ?? []), fn ($value) => $value !== null && $value !== ''));
        }

        return parent::castDehydratedState($state);
    }

    protected function getFieldProps(): array
    {
        $ajax_url = $this->evaluate($this->ajax_url);

        $props = [
            'options' => $ajax_url === null ? $this->getOptions() : null,
            'multiple' => $this->isMultiple() ?: null,
        ];

        if (! $this->isSearchable()) {
            return $props;
        }

        return [
            ...$props,
            'clearable' => $this->evaluate($this->allows_clear) ? null : false,
            'tags' => $this->evaluate($this->has_tags) ?: null,
            'isAjax' => $ajax_url !== null ?: null,
            'ajaxUrl' => $ajax_url,
            'nameField' => $this->ajax_fields[0] ?? null,
            'idField' => $this->ajax_fields[1] ?? null,
        ];
    }
}

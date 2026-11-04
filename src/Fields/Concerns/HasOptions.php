<?php

namespace Javaabu\Stencil\Fields\Concerns;

use BackedEnum;
use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\Rule;
use UnitEnum;

trait HasOptions
{
    /** @var array<int|string, mixed>|class-string<UnitEnum>|Arrayable|EloquentBuilder|Closure|null */
    protected mixed $options = null;

    protected bool $is_multiple = false;

    protected ?string $relationship = null;

    protected ?string $relationship_title = null;

    protected ?Closure $modify_relationship_query = null;

    protected bool|Closure $validates_options = true;

    /**
     * The choices: [value => label], a backed enum class (labels from labels()
     * or getLabel()), a collection, an Eloquent query (id => name), or a closure
     * returning any of these: fn (Get $get) => City::where('island_id', $get('island_id')).
     *
     * @param  array<int|string, mixed>|class-string<UnitEnum>|Arrayable|EloquentBuilder|Closure|null  $options
     */
    public function options(mixed $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Choices from a relationship of the form's model, saved on the relationship:
     * Select::make('category_id')->relationship('category', 'name') for a BelongsTo,
     * Select::make('tags')->multiple()->relationship('tags', 'name') for a BelongsToMany.
     */
    public function relationship(string $name, string $title_attribute = 'name', ?Closure $modify_query = null): static
    {
        $this->relationship = $name;
        $this->relationship_title = $title_attribute;
        $this->modify_relationship_query = $modify_query;

        return $this;
    }

    public function getRelationshipName(): ?string
    {
        return $this->relationship;
    }

    public function getRelationship(): ?Relation
    {
        if ($this->relationship === null) {
            return null;
        }

        return $this->getForm()->getModelInstance()->{$this->relationship}();
    }

    /**
     * Allow values that aren't in the options (no in: rule), for ajax and tag selects.
     */
    public function allowAnyOption(bool|Closure $condition = true): static
    {
        $this->validates_options = $condition instanceof Closure
            ? fn () => ! $this->evaluate($condition)
            : ! $condition;

        return $this;
    }

    public function validatesOptions(): bool
    {
        return (bool) $this->evaluate($this->validates_options);
    }

    public function multiple(bool $condition = true): static
    {
        $this->is_multiple = $condition;

        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->is_multiple;
    }

    /**
     * The options as [value => label].
     *
     * @return array<int|string, string>
     */
    public function getOptions(): array
    {
        if ($this->relationship !== null && $this->options === null) {
            return $this->getRelationshipOptions();
        }

        return static::normalizeOptions($this->evaluate($this->options));
    }

    /**
     * @return array<int|string, string>
     */
    protected function getRelationshipOptions(): array
    {
        $relationship = $this->getRelationship();
        $related = $relationship->getRelated();
        $query = $related->newQuery();

        if ($this->modify_relationship_query) {
            $query = $this->evaluate($this->modify_relationship_query, ['query' => $query], [EloquentBuilder::class => $query]) ?? $query;
        }

        return $query
            ->orderBy($related->qualifyColumn($this->relationship_title))
            ->pluck($related->qualifyColumn($this->relationship_title), $related->getQualifiedKeyName())
            ->all();
    }

    /**
     * @return array<int|string, string>
     */
    public static function normalizeOptions(mixed $options): array
    {
        if ($options === null) {
            return [];
        }

        if (is_string($options) && enum_exists($options)) {
            return static::enumOptions($options);
        }

        if ($options instanceof EloquentBuilder) {
            return $options->get()
                ->mapWithKeys(fn (Model $item) => [$item->getKey() => (string) ($item->name ?? $item->getKey())])
                ->all();
        }

        if ($options instanceof Arrayable) {
            $options = $options->toArray();
        }

        return array_map(fn ($label) => $label instanceof BackedEnum ? static::enumLabel($label) : (string) $label, (array) $options);
    }

    /**
     * @param  class-string<UnitEnum>  $enum
     * @return array<int|string, string>
     */
    protected static function enumOptions(string $enum): array
    {
        if (method_exists($enum, 'labels')) {
            return array_map('strval', $enum::labels());
        }

        $options = [];

        foreach ($enum::cases() as $case) {
            $value = $case instanceof BackedEnum ? $case->value : $case->name;

            $options[$value] = static::enumLabel($case);
        }

        return $options;
    }

    protected static function enumLabel(UnitEnum $case): string
    {
        if (method_exists($case, 'getLabel')) {
            return (string) $case->getLabel();
        }

        return $case->name;
    }

    /**
     * The in: rule for the option values.
     *
     * @return array<int, mixed>
     */
    protected function getOptionRules(): array
    {
        if (! $this->validatesOptions()) {
            return [];
        }

        return [Rule::in(array_map('strval', array_keys($this->getOptions())))];
    }

    /**
     * Reads relationship keys, for a select bound to a relationship.
     */
    protected function readRelationshipState(mixed $record): mixed
    {
        if (! $record instanceof Model) {
            return data_get($record, $this->getStatePath());
        }

        $relationship = $record->{$this->relationship}();

        if ($relationship instanceof BelongsToMany) {
            if (! $record->exists) {
                return null;
            }

            return $relationship->pluck($relationship->getRelated()->getQualifiedKeyName())->all();
        }

        if ($relationship instanceof BelongsTo) {
            return $record->getAttribute($relationship->getForeignKeyName());
        }

        return data_get($record, $this->getStatePath());
    }
}

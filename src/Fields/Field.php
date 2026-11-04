<?php

namespace Javaabu\Stencil\Fields;

use BackedEnum;
use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Fields\Concerns\CanBeTranslatable;
use Javaabu\Stencil\Rendering\Node;
use UnitEnum;

/**
 * A form field: it has a name, a value in the form's state, validation rules,
 * and renders as a javaabu/forms component.
 */
abstract class Field extends Component
{
    use CanBeTranslatable;

    /**
     * The javaabu/forms component it renders as, in Blade's spelling (text, select2, file-upload).
     */
    protected ?string $component = null;

    protected string $name;

    protected string|Closure|null $label = null;

    protected bool|Closure $show_label = true;

    protected mixed $default_state = null;

    protected bool|Closure $is_required = false;

    protected bool|Closure $is_disabled = false;

    protected string|Closure|null $placeholder = null;

    protected string|Htmlable|Closure|null $help = null;

    protected bool|Closure $is_inline = false;

    /** @var array<int, array<int, mixed>|string|Closure> */
    protected array $rules = [];

    /** @var array<string, string> */
    protected array $validation_messages = [];

    protected string|Closure|null $validation_attribute = null;

    protected bool|Closure|null $is_dehydrated = null;

    protected ?Closure $format_state_using = null;

    protected ?Closure $dehydrate_state_using = null;

    /** @var array<int, Closure> */
    protected array $after_state_updated = [];

    protected bool $is_live = false;

    protected ?int $live_debounce = null;

    protected bool $live_on_blur = false;

    final public function __construct(string $name)
    {
        $this->name = static::normalizeName($name);
    }

    public static function make(string $name): static
    {
        $field = app(static::class, ['name' => $name]);

        $field->setUp();

        return $field;
    }

    /**
     * Configure defaults for the field type. Called by make().
     */
    protected function setUp(): void {}

    public static function normalizeName(string $name): string
    {
        return trim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.');
    }

    /**
     * The field's path in the form's state, in dot notation: address.city.
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function getStatePath(): string
    {
        return $this->name;
    }

    /**
     * The name the control submits with, in bracket notation: address[city].
     */
    public function getInputName(): string
    {
        $segments = explode('.', $this->name);

        return array_shift($segments) . implode('', array_map(fn (string $segment) => "[{$segment}]", $segments));
    }

    /**
     * The id javaabu/forms gives the control: address[city] → address-city.
     */
    public function getId(): string
    {
        return str_replace('.', '-', $this->name);
    }

    /**
     * A CSS selector for the control, for conditions in the browser.
     */
    public function getSelector(): string
    {
        $primary = $this->primaryTranslatableInput();

        if ($primary !== null) {
            return '#' . $primary->name;
        }

        return '#' . $this->getId();
    }

    public function getKey(): string
    {
        return $this->key ?? $this->name;
    }

    public function getComponent(): ?string
    {
        return $this->component;
    }

    /*
    |--------------------------------------------------------------------------
    | Label, help and placeholder
    |--------------------------------------------------------------------------
    */

    public function label(string|Closure|null $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * The label: the given one, or one generated from the name like javaabu/forms
     * does (first_name → First Name, translated).
     */
    public function getLabel(): string
    {
        $label = $this->evaluate($this->label);

        if (filled($label)) {
            return (string) $label;
        }

        return $this->generateLabel();
    }

    protected function generateLabel(): string
    {
        $name = Str::afterLast($this->name, '.');

        return trans(Str::of($name)->camel()->snake()->replace('_', ' ')->title()->toString());
    }

    public function hiddenLabel(bool|Closure $condition = true): static
    {
        $this->show_label = $condition instanceof Closure
            ? fn () => ! $this->evaluate($condition)
            : ! $condition;

        return $this;
    }

    public function showsLabel(): bool
    {
        return (bool) $this->evaluate($this->show_label);
    }

    public function placeholder(string|Closure|null $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function getPlaceholder(): ?string
    {
        return $this->evaluate($this->placeholder);
    }

    /**
     * Help text under the field.
     */
    public function help(string|Htmlable|Closure|null $help): static
    {
        $this->help = $help;

        return $this;
    }

    public function helperText(string|Htmlable|Closure|null $help): static
    {
        return $this->help($help);
    }

    public function getHelp(): string|Htmlable|null
    {
        return $this->evaluate($this->help);
    }

    /**
     * Put the label beside the control instead of above it.
     */
    public function inline(bool|Closure $condition = true): static
    {
        $this->is_inline = $condition;

        return $this;
    }

    public function isInline(): bool
    {
        return (bool) $this->evaluate($this->is_inline);
    }

    /*
    |--------------------------------------------------------------------------
    | Required and disabled
    |--------------------------------------------------------------------------
    */

    public function required(bool|Closure $condition = true): static
    {
        $this->is_required = $condition;

        return $this;
    }

    public function isRequired(): bool
    {
        return (bool) $this->evaluate($this->is_required);
    }

    public function disabled(bool|Closure $condition = true): static
    {
        $this->is_disabled = $condition;

        return $this;
    }

    /**
     * Disabled by disabled(), the form (or its view operation), or a condition
     * (hidden by a condition counts). Disabled fields are rendered but not
     * validated or saved.
     */
    public function isDisabled(bool $with_conditions = true): bool
    {
        if ($this->evaluate($this->is_disabled)) {
            return true;
        }

        if ($this->getForm()->isDisabled()) {
            return true;
        }

        return $with_conditions && $this->isDisabledByConditions();
    }

    /*
    |--------------------------------------------------------------------------
    | State
    |--------------------------------------------------------------------------
    */

    /**
     * The value when the record has none, as in javaabu/forms.
     */
    public function default(mixed $state): static
    {
        $this->default_state = $state;

        return $this;
    }

    public function getDefaultState(): mixed
    {
        return $this->evaluate($this->default_state);
    }

    /**
     * Change the value read from the record before it reaches the form: fn ($state, $record) => ….
     */
    public function formatStateUsing(?Closure $callback): static
    {
        $this->format_state_using = $callback;

        return $this;
    }

    /**
     * Change the submitted value before it's returned from validate() and saved: fn ($state) => ….
     */
    public function dehydrateStateUsing(?Closure $callback): static
    {
        $this->dehydrate_state_using = $callback;

        return $this;
    }

    /**
     * Whether the field's value is returned from validate() and saved. Hidden and
     * disabled fields aren't, unless dehydrated(true).
     */
    public function dehydrated(bool|Closure $condition = true): static
    {
        $this->is_dehydrated = $condition;

        return $this;
    }

    public function isDehydrated(): bool
    {
        if ($this->is_dehydrated !== null) {
            return (bool) $this->evaluate($this->is_dehydrated);
        }

        if ($this->isHidden()) {
            return false;
        }

        return ! $this->isDisabled();
    }

    /**
     * Whether the field's value is saved as an attribute of the model. Relationship
     * fields save in saveRelationships() instead.
     */
    public function savesToAttribute(): bool
    {
        return true;
    }

    /**
     * The field's value from the record (or its default), as it goes into the form's state.
     */
    public function hydrateState(mixed $record): mixed
    {
        $state = $this->readFromRecord($record);

        if ($state === null) {
            $state = $this->getDefaultState();
        }

        if ($this->format_state_using) {
            $state = $this->evaluate($this->format_state_using, ['state' => $state, 'record' => $record]);
        }

        return $this->normalizeState($state);
    }

    protected function readFromRecord(mixed $record): mixed
    {
        if ($record === null) {
            return null;
        }

        return data_get($record, $this->getStatePath());
    }

    /**
     * Turns what the record holds (enums, collections, dates) into plain state.
     */
    public function normalizeState(mixed $state): mixed
    {
        if ($state instanceof BackedEnum) {
            return $state->value;
        }

        if ($state instanceof UnitEnum) {
            return $state->name;
        }

        if ($state instanceof Arrayable) {
            $state = $state->toArray();
        }

        if (is_array($state)) {
            return array_map(fn ($value) => $this->normalizeState($value), $state);
        }

        return $state;
    }

    /**
     * Writes the field's value from the record into the state. A translatable
     * field writes each input (name_en, name_dv), and its name holds the primary one.
     *
     * @param  array<string, mixed>  $state
     */
    public function hydrateStateInto(array &$state, mixed $record): void
    {
        $value = $this->hydrateState($record);

        data_set($state, $this->getStatePath(), $value);

        foreach ($this->translatableValues($record, $value) as $name => $translation) {
            $state[$name] = $this->normalizeState($translation);
        }
    }

    /**
     * Writes the field's value from submitted input (or old input) into the state.
     *
     * @param  array<string, mixed>  $state
     * @param  array<string, mixed>  $input
     */
    public function fillStateFrom(array &$state, array $input): void
    {
        $inputs = $this->getTranslatableInputs();

        if ($inputs === []) {
            data_set($state, $this->getStatePath(), $this->normalizeState(data_get($input, $this->getStatePath())));

            return;
        }

        foreach ($inputs as $locale_input) {
            $state[$locale_input->name] = $this->normalizeState($input[$locale_input->name] ?? null);

            if ($locale_input->is_primary) {
                data_set($state, $this->getStatePath(), $state[$locale_input->name]);
            }
        }
    }

    /**
     * Writes the field's dehydrated value into validated data. A translatable
     * field writes each input.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $state
     */
    public function dehydrateInto(array &$data, array $state): void
    {
        foreach ($this->getDehydratedPaths() as $path) {
            data_set($data, $path, $this->dehydrateState(data_get($state, $path)));
        }
    }

    /**
     * Where the field's value is in validated data: its name, or each translatable input.
     *
     * @return array<int, string>
     */
    public function getDehydratedPaths(): array
    {
        $inputs = $this->getTranslatableInputs();

        if ($inputs === []) {
            return [$this->getStatePath()];
        }

        return array_values(array_map(fn ($input) => $input->name, $inputs));
    }

    /**
     * The field's own value in the form's state.
     */
    public function getState(): mixed
    {
        return $this->getForm()->getStateValue($this->getStatePath());
    }

    /**
     * The submitted value as returned from validate().
     */
    public function dehydrateState(mixed $state): mixed
    {
        $state = $this->castDehydratedState($state);

        if ($this->dehydrate_state_using) {
            return $this->evaluate($this->dehydrate_state_using, ['state' => $state]);
        }

        return $state;
    }

    protected function castDehydratedState(mixed $state): mixed
    {
        return $state === '' ? null : $state;
    }

    /*
    |--------------------------------------------------------------------------
    | Live
    |--------------------------------------------------------------------------
    */

    /**
     * Re-evaluate the form on the server when this field changes, so closures
     * that read it with $get update. Text fields wait for typing to stop
     * (debounce, in milliseconds) or for the field to lose focus (on_blur).
     */
    public function live(bool $condition = true, ?int $debounce = null, bool $on_blur = false): static
    {
        $this->is_live = $condition;
        $this->live_debounce = $debounce;
        $this->live_on_blur = $on_blur;

        return $this;
    }

    public function isLive(): bool
    {
        return $this->is_live;
    }

    /**
     * Run when the field changes in a live form: fn (Set $set, ?string $state, $old) => $set('slug', Str::slug($state)).
     * Calling it makes the field live.
     */
    public function afterStateUpdated(Closure $callback): static
    {
        $this->after_state_updated[] = $callback;

        if (! $this->is_live) {
            $this->live();
        }

        return $this;
    }

    public function callAfterStateUpdated(mixed $old): void
    {
        foreach ($this->after_state_updated as $callback) {
            $this->evaluate($callback, ['old' => $old]);
        }
    }

    /**
     * @return array{debounce: int|null, onBlur: bool}|null
     */
    public function getLive(): ?array
    {
        if (! $this->is_live) {
            return null;
        }

        return [
            'debounce' => $this->live_debounce,
            'onBlur' => $this->live_on_blur,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    /**
     * Add validation rules: rules(['alpha_dash', new Uppercase]), rules('max:20|alpha').
     * A closure gets the usual arguments and returns rules.
     *
     * @param  string|array<int, mixed>|Closure  $rules
     */
    public function rules(string|array|Closure $rules): static
    {
        $this->rules[] = $rules;

        return $this;
    }

    /**
     * @param  array<string, string>  $messages  rule => message: ['required' => __('Enter a title.')]
     */
    public function validationMessages(array $messages): static
    {
        $this->validation_messages = array_merge($this->validation_messages, $messages);

        return $this;
    }

    /**
     * The name used in validation messages. Defaults to the label.
     */
    public function validationAttribute(string|Closure|null $attribute): static
    {
        $this->validation_attribute = $attribute;

        return $this;
    }

    public function getValidationAttribute(): string
    {
        return $this->evaluate($this->validation_attribute) ?? $this->getLabel();
    }

    /**
     * The rules for the field's value, without the ones for array items.
     *
     * @return array<int, mixed>
     */
    public function getValidationRules(): array
    {
        $rules = [$this->isRequired() ? 'required' : 'nullable', ...$this->getTypeRules()];

        foreach ($this->rules as $set) {
            $set = $this->evaluate($set);

            array_push($rules, ...(is_string($set) ? explode('|', $set) : Arr::wrap($set)));
        }

        return array_values(array_unique($rules, SORT_REGULAR));
    }

    /**
     * Rules that come with the field type (email for email inputs, in: for selects, …).
     *
     * @return array<int, mixed>
     */
    protected function getTypeRules(): array
    {
        return [];
    }

    /**
     * Every rule of the field, keyed by state path: the field's own, and those for array items.
     *
     * @return array<string, array<int, mixed>>
     */
    public function getAllValidationRules(): array
    {
        return $this->translateFieldRules([$this->getStatePath() => $this->getValidationRules()]);
    }

    /**
     * For a translatable field, rules per input; otherwise the rules as given.
     *
     * @param  array<string, array<int, mixed>>  $rules
     * @return array<string, array<int, mixed>>
     */
    protected function translateFieldRules(array $rules): array
    {
        if (! $this->isTranslatable()) {
            return $rules;
        }

        return $this->translateRules($rules);
    }

    /**
     * Names for validation messages, keyed by input: the label, or "Name (ދިވެހި)" per locale.
     *
     * @return array<string, string>
     */
    public function getValidationAttributes(): array
    {
        $attribute = $this->getValidationAttribute();
        $inputs = $this->getTranslatableInputs();

        if ($inputs === []) {
            return [$this->getStatePath() => $attribute];
        }

        $attributes = [];

        foreach ($inputs as $input) {
            $attributes[$input->name] = $input->label($attribute);
        }

        return $attributes;
    }

    /**
     * @return array<string, string> "path.rule" => message
     */
    public function getValidationMessages(): array
    {
        $messages = [];

        foreach ($this->validation_messages as $rule => $message) {
            if (str_contains($rule, '.')) {
                $messages[$rule] = $message;

                continue;
            }

            foreach ($this->getDehydratedPaths() as $path) {
                $messages["{$path}.{$rule}"] = $message;
            }
        }

        return $messages;
    }

    /*
    |--------------------------------------------------------------------------
    | Rendering
    |--------------------------------------------------------------------------
    */

    /**
     * The javaabu/forms props, camelCase, as @javaabu/forms takes them. The Blade
     * driver passes the same props as kebab-case attributes.
     *
     * @return array<string, mixed>
     */
    public function getProps(): array
    {
        return array_filter([
            'label' => $this->getLabel(),
            'showLabel' => $this->showsLabel() ? null : false,
            'required' => $this->isRequired() ?: null,
            'disabled' => $this->isDisabled(with_conditions: false) ?: null,
            'inline' => $this->isInline() ?: null,
            'help' => $this->getHelp(),
            ...$this->getPlaceholderProps(),
            ...$this->getFieldProps(),
        ], fn ($value) => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getPlaceholderProps(): array
    {
        $placeholder = $this->getPlaceholder();

        if ($placeholder === null) {
            return [];
        }

        return ['placeholder' => $placeholder, 'showPlaceholder' => true];
    }

    /**
     * Props of the field type. Null values are left out.
     *
     * @return array<string, mixed>
     */
    protected function getFieldProps(): array
    {
        return [];
    }

    public function toNode(): Node
    {
        $node = parent::toNode();

        $node->name = $this->getInputName();
        $node->component = $this->getComponent();

        if (! $node->hidden) {
            $node->live = $this->getLive();
            $node->translatable = $this->getTranslatableForDriver();
        }

        return $node;
    }

    /**
     * @return array<string, Closure>
     */
    protected function closureArgumentsByName(): array
    {
        return [
            ...parent::closureArgumentsByName(),
            'state' => fn () => $this->getState(),
        ];
    }
}

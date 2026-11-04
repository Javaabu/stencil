<?php

namespace Javaabu\Stencil;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Contracts\SavesRelationships;
use Javaabu\Stencil\Exceptions\StencilException;
use Javaabu\Stencil\Fields\Field;
use Javaabu\Stencil\Fields\FileUpload;
use Javaabu\Stencil\Http\LiveToken;
use Javaabu\Stencil\Rendering\Node;
use Javaabu\Stencil\Testing\AssertableForm;
use JsonSerializable;
use Stringable;

/**
 * The root of a schema. Build one inline with Form::make()->schema([...]), or
 * extend it and return the components from definition().
 */
class Form extends Component implements Arrayable, Htmlable, JsonSerializable, Stringable
{
    protected string $type = 'form';

    protected ?string $id = null;

    protected mixed $record = null;

    /** @var class-string<Model>|null */
    protected ?string $model_class = null;

    protected ?string $operation = null;

    protected string|Closure|null $action = null;

    protected string|Closure|null $method = null;

    protected bool|Closure|null $has_files = null;

    protected bool|Closure $is_disabled = false;

    protected bool|string|Closure|null $submit = null;

    protected bool $is_embedded = false;

    protected bool|Closure $has_summary = false;

    protected ?string $error_bag = null;

    /** @var array<string, mixed> */
    protected array $context = [];

    /** @var array<string, mixed>|null */
    protected ?array $state = null;

    /** @var array<int, string> */
    protected array $changed = [];

    /** @var array<string, Field>|null */
    protected ?array $fields = null;

    public static function make(?string $id = null): static
    {
        $form = app(static::class);

        if ($id !== null) {
            $form->id($id);
        }

        $form->setUp();

        return $form;
    }

    /**
     * Configure the form. Called by make(); override it in a form class.
     */
    protected function setUp(): void {}

    /**
     * The components of a form class. Inline forms use schema() instead.
     *
     * @return array<int, Component|null>
     */
    protected function definition(): array
    {
        return [];
    }

    protected function resolveChildComponents(): array|Closure
    {
        if ($this->child_components === []) {
            return $this->definition();
        }

        return $this->child_components;
    }

    public function schema(array|Closure $components): static
    {
        $this->fields = null;

        return parent::schema($components);
    }

    /*
    |--------------------------------------------------------------------------
    | Identity
    |--------------------------------------------------------------------------
    */

    /**
     * The form's id: the HTML id, and its key in the rendered output.
     */
    public function id(?string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): string
    {
        if ($this->id !== null) {
            return $this->id;
        }

        if (static::class === self::class) {
            return 'stencil-form';
        }

        return Str::kebab(class_basename(static::class));
    }

    public function getKey(): string
    {
        return $this->key ?? $this->getId();
    }

    /*
    |--------------------------------------------------------------------------
    | Record and operation
    |--------------------------------------------------------------------------
    */

    /**
     * What the form edits: a model (bound and saved), a model class (for a create
     * form), or an array or object to read values from (settings, filters).
     */
    public function model(mixed $record): static
    {
        if (is_string($record) && is_a($record, Model::class, true)) {
            $this->model_class = $record;
            $this->record = null;

            return $this->resetState();
        }

        $this->record = $record;

        if ($record instanceof Model) {
            $this->model_class = $record::class;
        }

        return $this->resetState();
    }

    public function record(mixed $record): static
    {
        return $this->model($record);
    }

    public function getRecord(): mixed
    {
        return $this->record;
    }

    /**
     * @return class-string<Model>|null
     */
    public function getModelClass(): ?string
    {
        return $this->model_class;
    }

    /**
     * The record, or a new instance of the model class: for relationships and saving.
     */
    public function getModelInstance(): Model
    {
        if ($this->record instanceof Model) {
            return $this->record;
        }

        if ($this->model_class === null) {
            throw StencilException::noModel();
        }

        return new ($this->model_class);
    }

    /**
     * create, edit, view or your own. Defaults to edit for an existing record and create otherwise.
     */
    public function operation(?string $operation): static
    {
        $this->operation = $operation;

        return $this;
    }

    public function getOperation(): string
    {
        if ($this->operation !== null) {
            return $this->operation;
        }

        if ($this->record instanceof Model) {
            return $this->record->exists ? 'edit' : 'create';
        }

        return $this->record === null ? 'create' : 'edit';
    }

    /**
     * Values the form needs besides the record, available as $this->context() in
     * definition() and kept when a live form is re-evaluated. Models are kept by key.
     *
     * @param  array<string, mixed>  $context
     */
    public function with(array $context): static
    {
        $this->context = array_merge($this->context, $context);

        return $this;
    }

    public function context(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->context;
        }

        return Arr::get($this->context, $key, $default);
    }

    /*
    |--------------------------------------------------------------------------
    | Submission
    |--------------------------------------------------------------------------
    */

    public function action(string|Closure|null $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->evaluate($this->action);
    }

    /**
     * GET, POST, PUT, PATCH or DELETE. Defaults to PATCH for an existing model, POST otherwise.
     */
    public function method(string|Closure|null $method): static
    {
        $this->method = $method;

        return $this;
    }

    public function getMethod(): string
    {
        $method = $this->evaluate($this->method);

        if ($method !== null) {
            return strtoupper($method);
        }

        return $this->record instanceof Model && $this->record->exists ? 'PATCH' : 'POST';
    }

    /**
     * Send the form as multipart/form-data. On by itself when the form has a file field.
     */
    public function files(bool|Closure|null $condition = true): static
    {
        $this->has_files = $condition;

        return $this;
    }

    public function hasFiles(): bool
    {
        $files = $this->evaluate($this->has_files);

        if ($files !== null) {
            return (bool) $files;
        }

        foreach ($this->getFields() as $field) {
            if ($field instanceof FileUpload) {
                return true;
            }
        }

        return false;
    }

    /**
     * The submit button after the fields: a label, true for the default label, or false for none.
     */
    public function submit(bool|string|Closure|null $submit = true): static
    {
        $this->submit = $submit;

        return $this;
    }

    public function getSubmitLabel(): ?string
    {
        if ($this->is_embedded) {
            return null;
        }

        $submit = $this->evaluate($this->submit) ?? config('stencil.submit', true);

        if ($submit === false) {
            return null;
        }

        return is_string($submit) ? $submit : __('stencil::strings.submit');
    }

    /**
     * Render only the fields, without the <form> tag, the submit button or the
     * live script: for putting the fields inside a form of your own.
     */
    public function embedded(bool $condition = true): static
    {
        $this->is_embedded = $condition;

        return $this;
    }

    public function isEmbedded(): bool
    {
        return $this->is_embedded;
    }

    /**
     * List every error at the top of the form, as well as under the fields.
     */
    public function summary(bool|Closure $condition = true): static
    {
        $this->has_summary = $condition;

        return $this;
    }

    public function hasSummary(): bool
    {
        return (bool) $this->evaluate($this->has_summary);
    }

    /**
     * Read errors from a named error bag.
     */
    public function errorBag(?string $bag): static
    {
        $this->error_bag = $bag;

        return $this;
    }

    public function getErrorBag(): ?string
    {
        return $this->error_bag;
    }

    /**
     * Disable every field. Forms for the view operation are disabled by themselves.
     */
    public function disabled(bool|Closure $condition = true): static
    {
        $this->is_disabled = $condition;

        return $this;
    }

    public function isDisabled(): bool
    {
        if ($this->getOperation() === 'view') {
            return true;
        }

        return (bool) $this->evaluate($this->is_disabled);
    }

    public function isHidden(): bool
    {
        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    */

    /**
     * Every field in the schema, hidden ones included, keyed by name.
     *
     * @return array<string, Field>
     */
    public function getFields(): array
    {
        if ($this->fields !== null) {
            return $this->fields;
        }

        $fields = [];

        foreach ($this->getFlatFields() as $field) {
            if (array_key_exists($field->getStatePath(), $fields)) {
                throw StencilException::duplicateField($field->getStatePath());
            }

            $fields[$field->getStatePath()] = $field;
        }

        return $this->fields = $fields;
    }

    public function getField(string $name): ?Field
    {
        return $this->getFields()[Field::normalizeName($name)] ?? null;
    }

    public function hasField(string $name): bool
    {
        return $this->getField($name) !== null;
    }

    /*
    |--------------------------------------------------------------------------
    | State
    |--------------------------------------------------------------------------
    */

    /**
     * Set the form's state. Without an argument, fill it from the record: old
     * input, then the record's value, then each field's default.
     *
     * @param  array<string, mixed>|null  $state
     */
    public function fill(?array $state = null): static
    {
        $this->changed = [];

        if ($state === null) {
            $this->state = $this->hydrate();

            return $this;
        }

        $this->state = [];

        $filled = [];

        foreach ($this->getFields() as $field) {
            $field->fillStateFrom($filled, $state);
        }

        $this->state = $filled;

        return $this;
    }

    /**
     * Fill the state from submitted input (a request or an array).
     *
     * @param  Request|array<string, mixed>|null  $input
     */
    public function fillFromInput(Request|array|null $input = null): static
    {
        $input ??= request();

        return $this->fill($input instanceof Request ? $input->all() : $input);
    }

    /**
     * @return array<string, mixed>
     */
    protected function hydrate(): array
    {
        $state = [];

        $this->state = [];

        foreach ($this->getFields() as $field) {
            $field->hydrateStateInto($state, $this->record);
        }

        $request = app('request');

        if (! $request->hasSession()) {
            return $state;
        }

        $session = $request->session();

        if (! $session->hasOldInput()) {
            return $state;
        }

        $old = $session->getOldInput();

        foreach ($this->getFields() as $field) {
            $field->fillStateFrom($state, $old);
        }

        return $state;
    }

    protected function resetState(): static
    {
        $this->state = null;
        $this->changed = [];

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getState(): array
    {
        if ($this->state === null) {
            $this->fill();
        }

        return $this->state;
    }

    public function getStateValue(string $path, mixed $default = null): mixed
    {
        return data_get($this->getState(), Field::normalizeName($path), $default);
    }

    public function setStateValue(string $path, mixed $value): void
    {
        $this->getState();

        $path = Field::normalizeName($path);

        data_set($this->state, $path, $value);

        $this->changed[] = $path;
    }

    /**
     * The fields whose value a closure changed with $set.
     *
     * @return array<int, string>
     */
    public function getChangedPaths(): array
    {
        return array_values(array_unique($this->changed));
    }

    /*
    |--------------------------------------------------------------------------
    | Validation and saving
    |--------------------------------------------------------------------------
    */

    /**
     * The fields that are validated: visible and enabled.
     *
     * @return array<string, Field>
     */
    public function getValidatedFields(): array
    {
        return array_filter($this->getFields(), fn (Field $field) => ! $field->isHidden() && ! $field->isDisabled());
    }

    /**
     * Validation rules for the current state, keyed by field name in dot notation.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [];

        foreach ($this->getValidatedFields() as $field) {
            $rules = array_merge($rules, $field->getAllValidationRules());
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $messages = [];

        foreach ($this->getValidatedFields() as $field) {
            $messages = array_merge($messages, $field->getValidationMessages());
        }

        return $messages;
    }

    /**
     * The fields' labels, for validation messages.
     *
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        $attributes = [];

        foreach ($this->getValidatedFields() as $field) {
            $attributes = array_merge($attributes, $field->getValidationAttributes());
        }

        return $attributes;
    }

    /**
     * A validator for the input, with the rules of the fields visible for it.
     *
     * @param  Request|array<string, mixed>|null  $input
     */
    public function validator(Request|array|null $input = null): ValidatorContract
    {
        $input ??= request();

        $data = $input instanceof Request ? $input->all() : $input;

        $this->fillFromInput($data);

        return Validator::make($data, $this->rules(), $this->messages(), $this->validationAttributes());
    }

    /**
     * Validate the input and return the values of the visible, enabled fields,
     * dehydrated. Throws a ValidationException when it fails.
     *
     * @param  Request|array<string, mixed>|null  $input
     * @return array<string, mixed>
     */
    public function validate(Request|array|null $input = null): array
    {
        $validator = $this->validator($input);

        if ($this->error_bag !== null) {
            $validator->validateWithBag($this->error_bag);
        } else {
            $validator->validate();
        }

        return $this->dehydrate();
    }

    /**
     * The values of the fields that are dehydrated, from the current state.
     *
     * @return array<string, mixed>
     */
    public function dehydrate(): array
    {
        $data = [];

        $state = $this->getState();

        foreach ($this->getFields() as $field) {
            if (! $field->isDehydrated()) {
                continue;
            }

            $field->dehydrateInto($data, $state);
        }

        return $data;
    }

    /**
     * Fill a model's attributes from validated data (fillable attributes only).
     * Relationships are saved by saveRelationships(), after the model is saved.
     *
     * @param  array<string, mixed>|null  $data  validated data; validates the request when null
     */
    public function fillModel(?Model $record = null, ?array $data = null): Model
    {
        $record ??= $this->getModelInstance();
        $data ??= $this->validate();

        $attributes = [];

        foreach ($this->getFields() as $field) {
            if (! $field->savesToAttribute()) {
                continue;
            }

            foreach ($field->getDehydratedPaths() as $path) {
                if (! Arr::has($data, $path)) {
                    continue;
                }

                $root = Str::before($path, '.');

                $attributes[$root] = $data[$root];
            }
        }

        return $record->fill($attributes);
    }

    /**
     * Validate the request (or take validated data), fill the record (or a new
     * model), save it and its relationships.
     *
     * @param  array<string, mixed>|null  $data
     */
    public function save(?array $data = null): Model
    {
        $data ??= $this->validate();

        $record = $this->fillModel($this->getModelInstance(), $data);

        $record->save();

        $this->saveRelationships($record, $data);

        $this->model($record);

        return $record;
    }

    /**
     * Save the relationship fields (BelongsToMany selects, media) of a saved model.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveRelationships(Model $record, array $data): void
    {
        foreach ($this->getFields() as $path => $field) {
            if (! $field instanceof SavesRelationships) {
                continue;
            }

            if (! Arr::has($data, $path)) {
                continue;
            }

            $field->saveRelationship($record, Arr::get($data, $path));
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Live fields
    |--------------------------------------------------------------------------
    */

    public function hasLiveFields(): bool
    {
        foreach ($this->getFields() as $field) {
            if ($field->isLive()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the server can rebuild this form to re-evaluate it.
     */
    public function canBeRebuilt(): bool
    {
        return static::class !== self::class;
    }

    /**
     * Allow re-evaluating this form from the live route. Override to check
     * permissions; the route's middleware runs first.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Where and how drivers send the state when a live field changes.
     *
     * @return array{url: string, token: string, debounce: int}|null
     */
    public function getLiveConfig(): ?array
    {
        if ($this->is_embedded) {
            return null;
        }

        $live = Arr::first($this->getFields(), fn (Field $field) => $field->isLive());

        if (! $live) {
            return null;
        }

        if (! $this->canBeRebuilt()) {
            throw StencilException::liveNeedsFormClass($live->getStatePath());
        }

        $route = config('stencil.live.route');

        if (! Route::has($route)) {
            Route::getRoutes()->refreshNameLookups();
        }

        if (! Route::has($route)) {
            throw StencilException::liveRouteMissing($route);
        }

        return [
            'url' => route($route, [], false),
            'token' => LiveToken::issue($this),
            'debounce' => (int) config('stencil.live.debounce', 500),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Rendering
    |--------------------------------------------------------------------------
    */

    public function getProps(): array
    {
        return array_filter([
            'id' => $this->getId(),
            'action' => $this->getAction(),
            'method' => $this->getMethod(),
            'files' => $this->hasFiles() ?: null,
            'operation' => $this->getOperation(),
            'submit' => $this->getSubmitLabel(),
            'summary' => $this->hasSummary() ?: null,
            'errorBag' => $this->getErrorBag(),
            'embedded' => $this->isEmbedded() ?: null,
            'columns' => $this->getColumns(),
        ], fn ($value) => $value !== null);
    }

    public function toNode(): Node
    {
        $this->getState();

        return parent::toNode();
    }

    /**
     * Render with a driver: blade, inertia (json), or one added with Stencil::extend().
     * Without one, the driver in config('stencil.views').
     */
    public function render(?string $driver = null): mixed
    {
        return app(StencilManager::class)->render($this, $driver);
    }

    /**
     * Assertions on this form, for tests.
     */
    public function assertable(): AssertableForm
    {
        return AssertableForm::make($this);
    }

    public function toHtml(): string
    {
        return (string) $this->render('blade');
    }

    public function __toString(): string
    {
        return $this->toHtml();
    }

    /**
     * The schema as JSON for Inertia and other JavaScript drivers.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->render('json');
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

# javaabu/stencil API

## Form

`Form::make(?id)`, or a subclass with `definition(): array` and optional `setUp()` and `authorize(): bool`.

| Method | What |
| --- | --- |
| `schema([...])` | Components (inline forms) |
| `model($record\|Model::class\|array)` | What the form edits |
| `operation('create'\|'edit'\|'view'\|…)` | Default: edit for an existing record; `view` disables everything |
| `with([...])`, `context('key')` | Extra values kept for live re-evaluation |
| `action(url)`, `method('PUT')`, `files()` | Submission |
| `submit(false\|'Label')`, `summary()`, `errorBag('name')`, `disabled()`, `columns(2)`, `id('x')` | |
| `embedded()` | Only the fields |
| `getState()`, `getStateValue('a.b')`, `fill([...])`, `fillFromInput($request)` | State |
| `rules()`, `messages()`, `validationAttributes()`, `validator()`, `validate()`, `dehydrate()` | Validation |
| `fillModel($model, $data)`, `save(?$data)`, `saveRelationships($model, $data)` | Saving |
| `render('blade'\|'json')`, `toHtml()`, `toArray()`, `assertable()` | Output and tests |

## Every component

`key()`, `statePath('meta')` (nests the fields inside; on a field, replaces its path), `columnSpan(2\|'full'\|[...])`, `columnSpanFull()`, `hidden()`, `visible()`, `hiddenOn()`, `visibleOn()`, `visibleWhen(field, values)`, `hiddenWhen()`, `enabledWhen()`, `disabledWhen()`, `visibleWhenChecked(checkbox)` (and hidden/enabled/disabled), `extraAttributes([...])`, `view('blade.view')`, `meta([...])`.

## Layout and display

| Component | Make | Options |
| --- | --- | --- |
| `Section` | `make(heading, [...])` | `description()`, `columns()` |
| `Grid` | `make(columns, [...])` | |
| `Group` | `make([...])` | `columns()` |
| `Fieldset` | `make(label, [...])` | `columns()` |
| `Tabs` / `Tab` | `Tabs::make([Tab::make(label, [...])])` | `activeTab(key)`; tab `icon()`, `columns()`; keys `tab-{snake label}` |
| `Wizard` / `Step` | `Wizard::make([Step::make(label, [...])])` | `startOnStep(n)`; step `description()` |
| `Alert` | `make(message)` | `color('warning')`, `heading()`, `icon()` |
| `TextEntry` | `make(name)` | `label()`, `value(fn)`, `multiline()`, `inline()` |

## Every field

`label()`, `hiddenLabel()`, `placeholder()`, `help()`, `inline()`, `required()`, `disabled()`, `default()`, `rules()`, `validationMessages([rule => msg])`, `validationAttribute()`, `formatStateUsing(fn ($state, $record))`, `dehydrateStateUsing(fn ($state))`, `dehydrated(false)`, `live(debounce:, on_blur:)`, `afterStateUpdated(fn (Set $set, $state, $old))`, `translatable(layout:, require_all:)`.

## Fields

| Field | Options |
| --- | --- |
| `TextInput` | `email()`, `password()`, `tel()`, `url()`, `numeric()`, `integer()`, `minLength()`, `maxLength()`, `minValue()`, `maxValue()`, `step()`, `decimals()`, `prefix()`, `suffix()`, `unique(?table, ?column, ignore_record)`, `thaana()`, `autocomplete()` |
| `Textarea` | `rows()`, `autosize()`, `thaana()`, `minLength()`, `maxLength()` |
| `Wysiwyg` | `rows()`, `maxLength()` |
| `Hidden` | |
| `Select` | `options()`, `relationship(name, title, ?fn ($query))`, `multiple()`, `searchable()`, `clearable(false)`, `tags()`, `ajax(url, name_field, id_field)`, `allowAnyOption()` |
| `Checkbox` | boolean; required = accepted |
| `CheckboxList` | `options()`, `relationship()`, `gridColumns()` |
| `Radio` | `options()`, `gridColumns()` |
| `DatePicker`, `DateTimePicker`, `TimePicker` | `minDate()`, `maxDate()`; state `Y-m-d`, `Y-m-d H:i`, `H:i` |
| `FileUpload`, `ImageUpload` | `fileType()`, `acceptedFileTypes()`, `maxSize(kb)`, `collection()`, `disk()`, `directory()`, `multiple()`; image `previewSize()`, `circle()` |

Options: `[value => label]`, an enum class, a collection, an Eloquent query, or a closure.

## Closure arguments

By name: `$get`, `$set`, `$state`, `$old`, `$record`, `$model`, `$operation`, `$component`, `$form`. By type: `Get`, `Set`, `Form`, `Field`/`Component`, the model class, anything from the container.

## React (`@javaabu/stencil`)

`<StencilForm schema components action method submit onEvaluated onEvaluationError {...FormProps}>`, `<StencilFields schema>`, `<StencilProvider components={{ type: Component }}>`, `useStencil()`. Components get `{ node, renderChildren }`.

---
title: Fields
sidebar_position: 2
---

Every field takes its name: `TextInput::make('name')`. Names use dots or brackets for nested values (`address.city` and `address[city]` are the same field), give the control its id (`address-city`) and its label (`City`, translated with `__()`), and are where its value lives in the model, the request and the errors.

# Every field

| Method | What |
| --- | --- |
| `label(string\|Closure)` | The label. Default: generated from the name like javaabu/forms (`first_name` → `First Name`, translated) |
| `hiddenLabel()` | No label |
| `placeholder(string)` | Placeholder text |
| `help(string\|Htmlable)` | Help under the field (`helperText()` is the same) |
| `inline()` | Label beside the control |
| `required(bool\|Closure)` | Required: the `required` rule, the attribute and the marker |
| `disabled(bool\|Closure)` | Disabled: rendered, not validated or saved |
| `default(mixed)` | The value when the record has none |
| `rules(array\|string\|Closure)` | More validation rules |
| `validationMessages(array)` | Messages by rule: `['required' => __('Enter a title.')]` |
| `validationAttribute(string)` | The name in messages; default the label |
| `formatStateUsing(Closure)` | Change the record's value before it reaches the form |
| `dehydrateStateUsing(Closure)` | Change the submitted value before it's saved |
| `dehydrated(bool)` | Leave the value out of `validate()` and saving (`password_confirmation`) |
| `live()`, `afterStateUpdated()` | See [Live fields](./live-fields.md) |
| `translatable()` | See [Translatable fields](../advanced-usage/translatable.md) |
| `extraAttributes(array)` | Pass HTML attributes or javaabu/forms attributes stencil has no method for (`['class' => 'font-mono', 'data-id' => 5]`) |
| `columnSpan()`, `columnSpanFull()` | See [Layout](./layout.md) |
| `visible()`, `hidden()`, `visibleWhen()`, … | See [Closures and conditions](./closures-and-conditions.md) |
| `view(string)` | Render with your own Blade view |
| `key(string)` | The key in the output; default the name |
| `meta(array)` | Extra data for drivers and custom components |

Any of these takes a closure. See [Closures and conditions](./closures-and-conditions.md).

# Text

`TextInput` is `<x-forms::text>` and its siblings:

```php
TextInput::make('name')->required()->maxLength(255)->thaana();
TextInput::make('email')->email()->unique();
TextInput::make('password')->password()->minLength(8)->visibleOn('create');
TextInput::make('phone')->tel()->prefix('+960');
TextInput::make('website')->url();
TextInput::make('price')->numeric()->minValue(0)->step(0.01)->decimals(2)->suffix('MVR');
TextInput::make('quantity')->integer()->minValue(1)->maxValue(10);
```

| Method | Renders | Rules |
| --- | --- | --- |
| (default) | `text` | `string` |
| `email()` | `email` | `string`, `email` |
| `password()` | `password`, never filled | `string` |
| `tel()` | `tel` | `string` |
| `url()` | `url` | `string`, `url` |
| `numeric()` | `number` | `numeric` |
| `integer()` | `number` with `step="1"` | `integer`; saved as an `int` |
| `minLength()`, `maxLength()` | `minlength`, `maxlength` | `min:`, `max:` |
| `minValue()`, `maxValue()`, `step()` | `min`, `max`, `step` | `min:`, `max:` |
| `unique(?table, ?column, ignore_record: true)` | | `unique:`, ignoring the record being edited |
| `prefix()`, `suffix()` | the `prepend` and `append` slots | |
| `decimals(int)` | rounds as you type | |
| `thaana()` | Thaana typing, right to left (text only) | |
| `autocomplete(string)` | `autocomplete` | |

`Textarea` takes `rows()`, `autosize()`, `thaana()`, `minLength()` and `maxLength()`. `Wysiwyg` is the rich text editor (TinyMCE in Blade, Onu's editor in React). `Hidden` is a hidden input.

# Choices

```php
use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\CheckboxList;
use Javaabu\Stencil\Fields\Radio;
use Javaabu\Stencil\Fields\Select;

Select::make('status')->options(ArticleStatuses::class)->required();
Select::make('atoll_id')->relationship('atoll', 'name')->searchable();
Select::make('tags')->multiple()->relationship('tags', 'name');
Select::make('island_id')->options(fn (Get $get) => Island::where('atoll_id', $get('atoll_id'))->pluck('name', 'id'));
Select::make('keywords')->multiple()->tags();
Select::make('owner_id')->ajax(route('api.users.index'), name_field: 'name', id_field: 'id');

Checkbox::make('is_published');
CheckboxList::make('channels')->options(['sms' => 'SMS', 'email' => __('Email')])->gridColumns(2);
Radio::make('gender')->options(Genders::class);
```

Options are `[value => label]` arrays, a backed enum class (labels from its `labels()` or `getLabel()`, as with javaabu/helpers' `NativeEnumsTrait`), a collection, an Eloquent query (`id` → `name`), or a closure returning any of them.

| Method | What |
| --- | --- |
| `options(…)` | The choices. Validated with an `in:` rule |
| `relationship(name, title_attribute, ?modify_query)` | Choices from a relationship of the form's model, ordered by the title. A `BelongsTo` saves the foreign key (name the field `atoll_id`); a `BelongsToMany` is synced after the model is saved (name the field `tags`) |
| `multiple()` | Several values: the name gets `[]`, the rules `array` and `name.*` |
| `searchable()` | `<x-forms::select2>` / `<Select2>` |
| `clearable(false)` | No clear button on a searchable select |
| `tags()` | Searchable, and accepts values that aren't options |
| `ajax(url, name_field, id_field)` | Search on the server; accepts any value |
| `allowAnyOption()` | No `in:` rule |
| `gridColumns()` | Checkbox lists and radios: lay the options out in columns |

A `Checkbox` is a boolean: unchecked is saved as `false`, and a required checkbox must be accepted. A `CheckboxList` is an array (`channels[]`); with nothing checked it saves `[]`.

# Dates and times

```php
DatePicker::make('born_on')->maxDate(now());
DateTimePicker::make('published_at')->minDate(fn () => now());
TimePicker::make('opens_at');
```

The state is a string: `Y-m-d`, `Y-m-d H:i`, `H:i`. Carbon values on the model are formatted for you. Dates are validated with `date` (times with `date_format:H:i`), `minDate()` adds `after_or_equal:` and `maxDate()` `before_or_equal:`.

# Files

```php
FileUpload::make('cv')->fileType('document')->maxSize(2048);
FileUpload::make('attachments')->multiple()->collection('attachments');
ImageUpload::make('avatar')->collection('avatar')->previewSize(200)->circle();
FileUpload::make('scan')->disk('s3')->directory('scans');
```

| Method | What |
| --- | --- |
| `fileType(string)` | javaabu/helpers' `AllowedMimeTypes` type: `document`, `image`, … |
| `acceptedFileTypes(array)` | Mime types, validated with `mimetypes:` |
| `maxSize(int)` | Kilobytes, validated with `max:` |
| `collection(string)` | Add the upload to this spatie/laravel-medialibrary collection after the model is saved |
| `disk()`, `directory()` | Without a collection, store the upload there and save its path to the attribute |
| `multiple()` | Several files (`FileUpload` only) |

Without a new upload, the model keeps its file, and a required file is satisfied by the one it has.

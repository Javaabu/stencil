---
title: Closures and conditions
sidebar_position: 4
---

# Closures

Every prop takes a closure instead of a value: labels, options, `required()`, `visible()`, `default()`, `rules()`, `columns()`, a whole `schema()`. Stencil calls it when it needs the value, with the arguments it asks for, by name:

| Argument | Value |
| --- | --- |
| `$get` | Reads the form's state: `$get('type')`, `$get('address.city')`, `$get()` for all of it |
| `$set` | Changes it: `$set('slug', Str::slug($state))` (in `afterStateUpdated()`) |
| `$state` | A field's own value; the whole state elsewhere |
| `$old` | The value before the change (in `afterStateUpdated()`) |
| `$record` | What the form edits: the model, the array, or `null` |
| `$model` | The model class |
| `$operation` | `create`, `edit`, `view`, … |
| `$component` | The field or component |
| `$form` | The form |

or by type: `Get`, `Set`, `Form`, `Field` or `Component`, your model's class (`fn (?Island $record)`), or anything the container builds (`fn (Request $request)`). A parameter it can't resolve throws an exception naming the arguments it could have had.

```php
use Javaabu\Stencil\Get;

Select::make('island_id')
    ->options(fn (Get $get) => Island::where('atoll_id', $get('atoll_id'))->pluck('name', 'id'))
    ->required(fn (Get $get) => filled($get('atoll_id')));

TextInput::make('code')->disabled(fn (?Island $record) => $record?->is_locked);

Section::make(__('Admin'))->visible(fn (Request $request) => $request->user()->can('manage', Island::class));
```

Closures run on the server, when the form renders and when it validates. They see the state of that moment: the record when the page loads, the request when it's submitted. To re-run them while the user edits the form, make the fields they read [live](./live-fields.md).

# Showing and hiding on the server

| Method | Hides the component when |
| --- | --- |
| `hidden(bool\|Closure)` | true |
| `visible(bool\|Closure)` | false |
| `hiddenOn('edit')`, `hiddenOn([...])` | the operation is one of these |
| `visibleOn('create')` | the operation isn't one of these |

A hidden component isn't rendered, isn't validated and isn't saved, and neither is anything inside it.

# Conditions in the browser

For the common "show this when that has a value" case, use a condition. It's applied in the browser as the user edits the form, with no request to the server, and on the server when the form is validated and saved:

```php
Radio::make('type')->options(['person' => __('Person'), 'company' => __('Company')]);

TextInput::make('id_card')->visibleWhen('type', 'person');
TextInput::make('company_name')->visibleWhen('type', 'company')->required();
TextInput::make('registration_no')->hiddenWhen('type', ['person', 'other']);

Checkbox::make('has_delivery');
Section::make(__('Delivery'))->visibleWhenChecked('has_delivery')->schema([...]);

DatePicker::make('published_on')->enabledWhen('status', ArticleStatuses::PUBLISHED);
TextInput::make('max_salary')->disabledWhenChecked('no_max_salary');
```

| Method | The component is |
| --- | --- |
| `visibleWhen(field, values)` | shown (and enabled) while the field has one of the values |
| `hiddenWhen(field, values)` | hidden (and disabled) while it does |
| `enabledWhen(field, values)` | enabled while it does, disabled otherwise |
| `disabledWhen(field, values)` | disabled while it does |
| `…WhenChecked(checkbox)` | the same, for a checkbox being checked |

Values can be one value, an array, an enum case or a collection. For a multiple select or a checkbox list, any selected value matches. Several conditions all apply.

They're rendered with javaabu/forms' conditional wrapper in both drivers: hidden fields are disabled, so the browser doesn't submit them, and the server leaves them out of the rules and the saved data, so `required()` on a hidden field doesn't fail.

**Which to use:** conditions for anything that depends only on another field's value, since they're instant and work in inline forms. Closures for anything else (the user, the record, a query), and with live fields when they depend on what the user picks.

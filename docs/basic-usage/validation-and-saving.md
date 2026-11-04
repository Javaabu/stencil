---
title: Validation and saving
sidebar_position: 6
---

# The form's state

The state is the form's values, nested by field name. It's filled, for each field, from:

1. old input, after a failed validation (when there's any old input, a field missing from it is empty, as in javaabu/forms);
2. the record: `data_get($record, 'address.city')`, with enums as their values, dates as strings in the picker's format, `BelongsTo` selects as the foreign key and `BelongsToMany` selects as the related keys;
3. the field's `default()`.

`formatStateUsing(fn ($state, $record) => …)` changes the value read from the record. Read the state with `$form->getState()` or `$form->getStateValue('address.city')`, and set it with `$form->fill([...])`.

Both drivers render from the state: Blade binds it to `<x-forms::form :model>`, and the JSON driver sends it as `state`.

# Validating

```php
public function update(Request $request, Island $island)
{
    $data = IslandForm::make()->model($island)->validate($request);

    $island->update($data);

    return to_route('islands.edit', $island);
}
```

`validate()` fills the state from the request, works out the rules for the fields that are visible and enabled for that state, validates, and returns the values of those fields (dehydrated). Unknown keys in the request are left out, so it's safe to `fill()` or `update()` with.

The rules come from the schema:

- `required` or `nullable`,
- the field type's rules (`string`, `email`, `numeric`, `date`, `boolean`, `array`, `file`, …),
- `in:` the options of selects, radios and checkbox lists,
- `min:`/`max:` from `minLength()`, `maxLength()`, `minValue()`, `maxValue()`, dates from `minDate()`/`maxDate()`, `unique()`,
- then your `rules()`.

The fields' labels are the attribute names in the messages ("The ސުރުހީ field is required."), and `validationMessages()` sets messages per rule.

| Method | Returns |
| --- | --- |
| `validate(Request\|array\|null)` | The validated, dehydrated values. Throws a `ValidationException` (into `errorBag()` when set) |
| `validator(Request\|array\|null)` | A validator, to add `after()` hooks yourself |
| `rules()`, `messages()`, `validationAttributes()` | For the current state, for a FormRequest of your own |
| `dehydrate()` | The dehydrated values of the current state |

In a FormRequest:

```php
public function rules(): array
{
    return $this->form()->fillFromInput($this)->rules();
}

public function attributes(): array
{
    return $this->form()->validationAttributes();
}
```

# What's saved

A field's value is returned and saved unless it's hidden, disabled, or `dehydrated(false)`. Values are cast on the way: empty strings become `null`, checkboxes `true`/`false`, checkbox lists and multiple selects arrays, `integer()` inputs `int`s, uploads stored paths. `dehydrateStateUsing(fn ($state) => …)` changes it last.

# Saving

```php
$island = IslandForm::make()->model(Island::class)->save();             // create from the request
$island = IslandForm::make()->model($island)->save();                   // update from the request
$island = IslandForm::make()->model($island)->save($data);              // with values you validated
```

`save()` validates the request (unless you pass the data), fills the model with `fill()` (so only fillable attributes), saves it, then saves the relationship fields: `BelongsToMany` selects and checkbox lists are synced, and files with a `collection()` are added to the media library.

For control over each step:

```php
$data = $form->validate($request);
$island = $form->fillModel($island, $data);   // fill, don't save
$island->user()->associate($request->user());
$island->save();
$form->saveRelationships($island, $data);
```

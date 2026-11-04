---
title: Forms
sidebar_position: 1
---

A form is the root of a schema. It knows what it edits (the record), where it submits, and its components.

# Inline forms

Build one where you need it, usually in a controller:

```php
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Stencil\Form;

$form = Form::make()
    ->model($island)
    ->action(route('islands.update', $island))
    ->schema([
        TextInput::make('name')->label(__('Name'))->required()->thaana(),
        TextInput::make('code')->maxLength(5),
    ]);
```

# Form classes

Put forms you reuse in a class, and return the components from `definition()`. A class is also what [live fields](./live-fields.md) need, because the server rebuilds the form to re-evaluate it.

```php
namespace App\Stencil;

use Javaabu\Stencil\Components\Section;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Stencil\Form;

class IslandForm extends Form
{
    protected function definition(): array
    {
        return [
            Section::make(__('Island'))->columns(2)->schema([
                TextInput::make('name')->label(__('Name'))->required()->thaana(),
                Select::make('atoll_id')->relationship('atoll', 'name')->required(),
            ]),
        ];
    }
}
```

```php
$form = IslandForm::make()->model($island)->action(route('islands.update', $island));
```

Override `setUp()` to configure the form itself (`$this->submit(__('Save island'))`), and `authorize()` to check permissions on live requests.

# What the form edits

`model()` takes:

| Value | Use |
| --- | --- |
| A model | Edit it: fields fill from it, and `save()` updates it |
| A model class | Create one: `->model(Island::class)`; relationship options come from a new instance |
| An array or object | Read values only, for settings, filters or search forms |

The operation is `edit` for an existing record and `create` otherwise. Set it yourself with `->operation('view')` (which disables every field) or any name of your own, and show components for some operations only with `visibleOn()` and `hiddenOn()`.

Values the form needs besides the record go in `with()`, and are kept when a live form is rebuilt. Models are kept by their key:

```php
CourseApplicationForm::make()->model(Application::class)->with(['course' => $course]);

// in the class
protected function definition(): array
{
    $course = $this->context('course');
    // …
}
```

# Submitting

| Method | Default |
| --- | --- |
| `action(string\|Closure)` | none |
| `method(string)` | `PATCH` for an existing model, `POST` otherwise. `PUT`, `PATCH` and `DELETE` are spoofed with `_method` |
| `files(bool)` | on when the form has a file field |
| `submit(bool\|string)` | a button labelled "Save" (`stencil::strings.submit`), or `config('stencil.submit')`; a string for another label; `false` for none |
| `summary()` | lists every error at the top as well as under each field |
| `errorBag(string)` | the error bag validation writes to and fields read from |
| `disabled()` | disables every field |
| `id(string)` | the HTML id; `stencil-form` for inline forms, the class name in kebab case (`island-form`) for classes |

# Rendering

## Blade

A form is `Htmlable`, so echo it:

```blade
<x-layouts.admin>
    {{ $form }}
</x-layouts.admin>
```

It renders `<x-forms::form>` with your components inside, bound to the form's state, and the submit button. The page needs manifest's tags (`@manifestStyles` and `@manifestScripts`) or its automatic injection, as for any javaabu/forms page.

## Inertia React

A form is `Arrayable` and `JsonSerializable`, so pass it as a prop:

```php
return Inertia::render('Islands/Edit', ['form' => IslandForm::make()->model($island)->action(route('islands.update', $island))]);
```

```tsx
import { StencilForm, type StencilSchema } from "@javaabu/stencil"

export default function Edit({ form }: { form: StencilSchema }) {
  return <StencilForm schema={form} />
}
```

See [React](./react.md).

## Choosing in config

Packages that render either way call `Stencil::render($form)`, which uses `config('stencil.views')`: `blade` (HTML) or `inertia` (the JSON array). `$form->render('blade')` and `$form->render('json')` pick a driver.

# Only the fields

`embedded()` renders the components without the `<form>`, the submit button or the live script, for putting them inside a form of your own:

```blade
<x-forms::form method="GET" :action="route('islands.index')">
    {{ $filters->embedded() }}
    <x-forms::submit>{{ __('Filter') }}</x-forms::submit>
</x-forms::form>
```

In React, `<StencilFields schema={filters} />` does the same.

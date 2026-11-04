---
title: Custom fields
sidebar_position: 2
---

A field type is three things: a PHP class, a Blade view and a React component. Packages and apps add their own.

# Generate one

```bash
php artisan make:stencil-field Rating
```

It writes:

| File | What |
| --- | --- |
| `app/Stencil/Fields/Rating.php` | The class: its type (`rating`), its view, a `max()` option, rules and props |
| `resources/views/stencil/fields/rating.blade.php` | The Blade view |
| `resources/js/stencil/fields/Rating.tsx` | The React component |

Pass `--force` to overwrite files that exist.

# The class

```php
namespace App\Stencil\Fields;

use Closure;
use Javaabu\Stencil\Fields\Field;

class Rating extends Field
{
    protected string $type = 'rating';

    protected ?string $view = 'stencil.fields.rating';

    protected int|Closure $max = 5;

    public function max(int|Closure $max): static
    {
        $this->max = $max;

        return $this;
    }

    protected function getTypeRules(): array
    {
        return ['integer', 'min:1', "max:{$this->evaluate($this->max)}"];
    }

    protected function getFieldProps(): array
    {
        return ['max' => $this->evaluate($this->max)];
    }
}
```

```php
Rating::make('stars')->label(__('Stars'))->max(10)->required();
```

Everything from `Field` works: labels, help, `required()`, closures, conditions, live, validation and saving. Override what your type needs:

| Method | For |
| --- | --- |
| `setUp()` | Defaults, called by `make()` |
| `getFieldProps()` | Props for the view and the component; `null` values are left out |
| `getTypeRules()` | Rules that come with the type |
| `getAllValidationRules()` | Rules for other keys, such as `name.*` |
| `readFromRecord()`, `normalizeState()` | Reading the value from the record |
| `castDehydratedState()` | The value returned from `validate()` |
| `getInputName()`, `getSelector()` | The submitted name (`tags[]`), and the selector conditions watch |
| `savesToAttribute()` + `SavesRelationships` | Saving somewhere other than an attribute, after the model is saved |

A field that renders as a javaabu/forms component sets `protected ?string $component = 'text'` instead of a view, and both drivers render that component with its props.

# The Blade view

The view gets `$node` (the resolved field: `name`, `key`, `props`, `prop()`) and `$stencil` (the Blade renderer):

```blade
<x-forms::form-group :name="$node->name" :label="$node->prop('label', '')" :required="(bool) $node->prop('required')">
    <input type="number" id="{{ $node->name }}" name="{{ $node->name }}" value="{{ $stencil->value($node) }}" max="{{ $node->prop('max') }}">
    <x-forms::errors :name="$node->name" />
</x-forms::form-group>
```

| Helper | What |
| --- | --- |
| `$stencil->value($node)` | The field's value: old input, then the form's state |
| `$stencil->component('text', [...props])` | A javaabu/forms component with props |
| `$stencil->field($node, [...override])` | The field's own component |
| `$stencil->children($nodes, $columns)` | Child nodes, for container types |

Instead of `$view` in the class, register the view for the type, for example in a package:

```php
Stencil::view('rating', 'my-package::stencil.rating');
```

# The React component

```tsx
import { FormGroup, useFieldValue } from "@javaabu/forms"
import type { StencilComponentProps } from "@javaabu/stencil"

export function Rating({ node }: StencilComponentProps) {
  const name = node.name ?? node.key
  const value = useFieldValue(name, undefined, undefined)
  return (
    <FormGroup name={name} label={node.props?.label as string} required={node.props?.required as boolean}>
      <input type="number" id={name} name={name} defaultValue={String(value ?? "")} max={node.props?.max as number} />
    </FormGroup>
  )
}
```

Register it by type with `StencilProvider components={{ rating: Rating }}` (see [React](../basic-usage/react.md#your-own-components)). A package that ships React components exports them from its own `resources/js` through javaabu/manifest, and tells apps to register them.

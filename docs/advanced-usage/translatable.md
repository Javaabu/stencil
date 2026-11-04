---
title: Translatable fields
sidebar_position: 6
---

With [javaabu/translatable](https://github.com/Javaabu/translatable) v3 installed, a field can take one input per language:

```php
TextInput::make('name')->label(__('Name'))->required()->translatable();
Textarea::make('description')->translatable(layout: 'stacked');
Wysiwyg::make('body')->translatable(fn (?Post $record) => $record?->is_public);
```

When translatable is installed, enabled, and the form's model translates the field (`Translatable::supports($model, 'name')`), the field becomes one input per active locale, named `{field}_{locale}`: `name_en`, `name_dv`. Otherwise (the package isn't installed, translations are off, or the model or field isn't translatable) it's a plain field, so `->translatable()` is always safe.

| | |
| --- | --- |
| Inputs | One per active locale, the model's primary locale first, each with its language's direction, `lang`, and the Thaana keyboard for Dhivehi. Labels are "Name (ދިވެހި)" |
| Layout | `tabs` (the tab with an error opens) or `stacked` |
| State | `name_en`, `name_dv` from `Translatable::values()`; `name` holds the primary locale's value, so `$get('name')` works |
| Validation | Your rules through `Translatable::rules()`: the primary locale as written, the others with `required` swapped for `nullable`. `translatable(require_all: true)` requires every locale |
| Saving | `validate()` returns `name_en` and `name_dv`, which translatable models accept in `fill()`, `create()` and `update()` |
| Conditions | `visibleWhen('name', …)` watches the primary locale's input |

Both drivers render the same inputs: Blade with javaabu/forms' tabs and inputs, React with `@javaabu/forms`. The JSON node has a `translatable` key with the inputs (see [JSON format](./json-format.md)). To use translatable's own React component, register it as the `translatable` type:

```tsx
<StencilProvider components={{ translatable: MyTranslatableField }}>
```

# Installing

Stencil only suggests translatable. Install it in your app to use translatable fields:

```bash
composer require javaabu/translatable:^3.0@dev
```

See translatable's docs for making models translatable.

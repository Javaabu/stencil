---
title: Customizing views
sidebar_position: 7
---

The Blade driver renders each type with a view, `stencil::components.{type}`, and javaabu/forms components inside it.

| View | Type |
| --- | --- |
| `form` | The form: `<x-forms::form>`, the components and the submit button |
| `components/field` | Any field with a javaabu/forms component |
| `components/section`, `fieldset`, `grid`, `group`, `tabs`, `tab`, `wizard`, `step` | Layouts |
| `components/alert`, `text-entry` | Display components |
| `components/checkbox-list`, `radio` (with `partials/options`) | Option groups |
| `components/translatable` | Translatable fields |

# Changing one

Publish the views and edit the ones you need (delete the rest, so you get the package's updates):

```bash
php artisan vendor:publish --tag=stencil-views
```

They're in `resources/views/vendor/stencil`.

# A view for one component

```php
Section::make(__('Danger zone'))->view('stencil.danger-section')->schema([...]);
```

# A view for a type

```php
// a service provider's boot()
Stencil::view('section', 'stencil.section');
```

Every view gets `$node` and `$stencil`; see [Custom fields](./custom-fields.md#the-blade-view) for the helpers. The javaabu/forms components inside follow `forms.framework` (Onu or Bootstrap 6), so publish forms' views to change their markup.

Every component is wrapped in `<div class="stencil-item" data-stencil-key="…">`. Keep the wrapper: grid spans and live updates use it.

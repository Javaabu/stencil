---
title: Introduction
sidebar_position: 1.0
---

# Stencil

[Stencil](https://github.com/Javaabu/stencil) lets you define a form once, in PHP, and render it with Blade ([javaabu/forms](https://github.com/Javaabu/forms)) or with Inertia React ([@javaabu/forms](https://github.com/Javaabu/react-forms)). The same schema fills itself from a model, derives its validation rules, and saves back to the model.

```php
use Javaabu\Stencil\Components\Section;
use Javaabu\Stencil\Fields\DateTimePicker;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Stencil\Form;
use Javaabu\Stencil\Get;

$form = Form::make()
    ->model($article)
    ->action(route('articles.update', $article))
    ->schema([
        Section::make(__('Details'))->columns(2)->schema([
            TextInput::make('title')->required()->maxLength(255)->thaana(),
            Select::make('status')->options(ArticleStatuses::class)->required(),
            Select::make('category_id')->relationship('category', 'name')->searchable(),
            TextInput::make('company_name')->visibleWhen('type', 'company'),
            DateTimePicker::make('published_at')->visible(fn (Get $get) => $get('status') === 'published'),
        ]),
    ]);
```

Render it in a Blade view:

```blade
{{ $form }}
```

Or send it to an Inertia React page:

```php
return Inertia::render('Articles/Edit', ['form' => $form]);
```

```tsx
import { StencilForm } from "@javaabu/stencil"

export default function Edit({ form }) {
  return <StencilForm schema={form} />
}
```

And in the controller that receives it:

```php
$article = $form->save();   // validates the request, fills the model, saves it and its relationships
```

# What's in the box

- **A fluent schema**: forms, layouts (sections, grids, fieldsets, tabs, wizards) and a field for each javaabu/forms component.
- **Closures for any prop**, with `$get`, `$set`, `$record`, `$operation` and more injected, like [Filament](https://filamentphp.com), without Livewire.
- **Conditions in the browser** (`visibleWhen()`), and **live fields** that re-evaluate closures on the server.
- **Model binding**: old input, the record (casts, enums, relationships) and defaults, with javaabu/forms' precedence.
- **Validation rules from the schema**, only for the fields the user can see, and saving to the model and its relationships.
- **Two drivers**: Blade and JSON (for Inertia and other JavaScript renderers), plus your own with `Stencil::extend()`.
- **A React renderer**, shipped through javaabu/manifest.
- **Custom field types**, testing helpers, optional translatable fields, and Dhivehi and RTL throughout.

Filters, bulk actions, settings, widgets, meta and form-builder in the Javaabu packages render their forms with Stencil.

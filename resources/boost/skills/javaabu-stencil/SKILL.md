---
name: javaabu-stencil
description: Define, render, validate and save Laravel forms with javaabu/stencil. Use when building a form with Javaabu\Stencil\Form (Form::make()->schema(), a form class with definition()), its fields (TextInput, Select, Checkbox, CheckboxList, Radio, DatePicker, FileUpload, …) and layouts (Section, Grid, Tabs, Wizard), closures with $get/$set, visibleWhen() conditions, live fields and Route::stencil(), validating and saving with $form->validate()/save(), rendering in Blade ({{ $form }}) or Inertia React (StencilForm from @javaabu/stencil), custom field types (make:stencil-field), drivers (Stencil::extend), translatable fields, or testing forms with AssertableForm.
license: MIT
metadata:
  author: Javaabu
---

# Forms with javaabu/stencil

The docs ship with the package: `vendor/javaabu/stencil/llms.txt` lists every page in `vendor/javaabu/stencil/docs/`.

| Task | Read |
| --- | --- |
| Define, render, validate and save a form | This file |
| Every field, layout and option | [references/api.md](references/api.md) |
| Custom field types, drivers, the JSON format, testing | [references/extending.md](references/extending.md) |

## A form class, used for create and edit

```php
<?php

namespace App\Stencil;

use App\Enums\ArticleStatuses;
use Illuminate\Support\Str;
use Javaabu\Stencil\Components\Section;
use Javaabu\Stencil\Fields\DateTimePicker;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Fields\Textarea;
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Stencil\Form;
use Javaabu\Stencil\Get;
use Javaabu\Stencil\Set;

class ArticleForm extends Form
{
    protected function definition(): array
    {
        return [
            Section::make(__('Article'))->columns(2)->schema([
                TextInput::make('title')
                    ->label(__('Title'))
                    ->required()
                    ->maxLength(255)
                    ->thaana()
                    ->live(on_blur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')->label(__('Slug'))->required()->unique(),
                Select::make('status')->label(__('Status'))->options(ArticleStatuses::class)->required()->live(),
                DateTimePicker::make('published_at')
                    ->label(__('Published at'))
                    ->visible(fn (Get $get) => $get('status') === ArticleStatuses::PUBLISHED->value),
                Select::make('category_id')->label(__('Category'))->relationship('category', 'name')->searchable(),
                Select::make('tags')->label(__('Tags'))->multiple()->relationship('tags', 'name'),
                Textarea::make('body')->label(__('Body'))->thaana()->columnSpanFull(),
            ]),
        ];
    }
}
```

```php
// routes/web.php, once, for live fields
Route::stencil()->middleware(['web', 'auth']);
```

## The controller

```php
public function create()
{
    return view('articles.create', [
        'form' => ArticleForm::make()->model(Article::class)->action(route('articles.store')),
    ]);
}

public function store(Request $request)
{
    $article = ArticleForm::make()->model(Article::class)->save();   // validates the request

    return to_route('articles.edit', $article);
}

public function edit(Article $article)
{
    return Inertia::render('Articles/Edit', [
        'form' => ArticleForm::make()->model($article)->action(route('articles.update', $article)),
    ]);
}

public function update(Request $request, Article $article)
{
    ArticleForm::make()->model($article)->save();

    return to_route('articles.edit', $article);
}
```

## Rendering

```blade
{{-- resources/views/articles/create.blade.php --}}
{{ $form }}
```

```tsx
// resources/js/pages/Articles/Edit.tsx
import { StencilForm, type StencilSchema } from "@javaabu/stencil"

export default function Edit({ form }: { form: StencilSchema }) {
  return <StencilForm schema={form} />
}
```

## Rules

- Use a form class for reused forms and for live fields. Inline `Form::make()->schema([...])` is fine for one-off forms without live fields.
- `->model()` takes a model (edit), a model class (create) or an array (settings, filters). The method defaults to `PATCH` for an existing model.
- Never set field values: they come from old input, the record, then `default()`. Enums, dates and relationships are handled.
- Don't write rules the schema gives: `required()`, `email()`, `numeric()`, `maxLength()`, `minValue()`, options (`in:`), `unique()`, dates. Add others with `->rules([...])`. Rules apply only to fields visible and enabled for the submitted state.
- `validate()` returns only the schema's dehydrated fields, so it's safe to `fill()`. `save()` validates, fills (fillable only), saves, syncs `BelongsToMany` selects and adds media.
- Conditions on another field's value: `visibleWhen('type', 'company')`, `hiddenWhen()`, `enabledWhen()`, `disabledWhen()`, `…WhenChecked('checkbox')`. They work in the browser with no request.
- Anything else: closures (`->visible(fn (Get $get, ?Article $record, string $operation) => …)`), plus `->live()` on the fields they read. `hiddenOn('edit')` / `visibleOn('create')` for operations.
- Name `BelongsTo` selects by their foreign key (`category_id`) and `BelongsToMany` selects by the relationship (`tags`).
- Every label, help text, heading and option goes through `__()`; `->thaana()` on fields that take Dhivehi.
- Inside your own `<form>`: `->embedded()` (Blade) / `<StencilFields schema={form} />` (React).
- Nest fields someone else defined under a key with `Group::make()->statePath('meta')->schema([...])`: names become `meta[subtitle]`, paths `meta.subtitle`.
- A form is `Htmlable`, not `Stringable`: `{{ $form }}` or `$form->toHtml()`, never `(string) $form`. Pass it to components as `:form="$form"`.

## Common mistakes

- `->live()` on an inline `Form::make()` form: throws. Move the schema into a class.
- Forgetting `Route::stencil()`: live forms throw "the [stencil.evaluate] route is not defined".
- Writing `visible(fn (Get $get) => $get('type') === 'company')` without `->live()` on `type`: it's only evaluated when the page renders. Use `visibleWhen('type', 'company')` instead.
- `Select::make('category')->relationship('category', 'name')`: name it `category_id`.
- Reading the request in the controller after `save()` to set extra attributes: use `fillModel()`, then set them, then `save()` and `saveRelationships()`.
- Comparing enum state with an enum: state holds values (`$get('status') === ArticleStatuses::PUBLISHED->value`).
- Testing by parsing HTML: use `$form->assertable()->fillState([...])->assertFieldVisible('published_at')`.

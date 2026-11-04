---
title: Live fields
sidebar_position: 5
---

A live field sends the form's state to the server when it changes. The server rebuilds the form with that state, runs the closures again, and the page updates the components that changed: options that depend on another field, fields that show for some values, a slug that follows the title.

```php
use Illuminate\Support\Str;
use Javaabu\Stencil\Get;
use Javaabu\Stencil\Set;

class IslandForm extends Form
{
    protected function definition(): array
    {
        return [
            Select::make('atoll_id')->relationship('atoll', 'name')->live(),
            Select::make('ward_id')
                ->options(fn (Get $get) => Ward::where('atoll_id', $get('atoll_id'))->pluck('name', 'id'))
                ->visible(fn (Get $get) => filled($get('atoll_id'))),

            TextInput::make('name')
                ->live(on_blur: true)
                ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug((string) $state))),
            TextInput::make('slug'),
        ];
    }
}
```

# Setup

1. Put the form in a [class](./forms.md#form-classes). The server rebuilds it from the class, so inline `Form::make()` forms can't have live fields (they throw an exception that says so).
2. Add the route once, with the middleware your pages use:

```php
// routes/web.php
Route::stencil();
Route::stencil()->middleware(['auth:admin']);
```

# Options

```php
->live()                       // on change; text fields wait until typing stops
->live(debounce: 1000)         // wait 1 second after typing
->live(on_blur: true)          // when the field loses focus
->afterStateUpdated(fn (Set $set, Get $get, $state, $old) => …)   // makes the field live
```

Text fields wait 500 milliseconds after typing stops (`stencil.live.debounce`). Selects, checkboxes and radios send right away.

`afterStateUpdated()` runs on the server, for the field that changed, before the form is rendered. `$set()` changes another field's value; the page replaces that field with its new value.

# What happens

1. The page posts the form's values (not its files) to the route, with the field that changed and a token.
2. The token is the form class, the record (by key), the operation, `with()` context, and the user, encrypted with your app key. It expires after `stencil.live.token_lifetime` minutes (12 hours by default), and only works for the user who loaded the page.
3. The server rebuilds the form, calls `authorize()` on it (override it to check permissions; the route's middleware runs first), fills it with the posted state, runs `afterStateUpdated()`, and renders it.
4. Blade: the script replaces only the components whose output changed, and the fields `$set` changed, so the field you're typing in keeps its focus. React: `StencilForm` re-renders with the new schema.

The Blade script fires `stencil:evaluated` on the form after an update and `stencil:error` when the request fails. React's `StencilForm` takes `onEvaluated` and `onEvaluationError`.

# Security

The route returns what your closures return for the posted state, such as options, labels and visibility, to the user the page was rendered for. Treat it like the page itself: give it the same middleware, and check anything more in `authorize()`:

```php
public function authorize(): bool
{
    return request()->user()?->can('update', $this->getRecord() ?? Island::class) ?? false;
}
```

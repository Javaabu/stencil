{{--
    javaabu/stencil guidelines for AI code assistants

    Maintained by: Javaabu (https://javaabu.com)
--}}

## javaabu/stencil

- Forms with more than a handful of fields are defined once with `javaabu/stencil` (`Javaabu\Stencil\Form`) and rendered by Blade (`@{{ $form }}`) or Inertia React (`<StencilForm schema={form} />`). Don't hand-write the same form in Blade and React, and don't duplicate its validation rules in a FormRequest: use `$form->validate($request)` and `$form->save()`.
- Bind the record with `->model($record)`; never pass values to fields. Use `visibleWhen()` for fields that depend on another field's value, closures (`fn (Get $get) => …`) for anything else, and `->live()` (in a form class, with `Route::stencil()`) when closures must update as the user edits.
- Follow Javaabu codestyle in schemas: imports for every component, `__()` for every label, help text, heading and option, `->thaana()` on text fields that take Dhivehi.
- Always activate the `javaabu-stencil` skill when defining, rendering, validating or saving stencil forms, writing custom field types or drivers, or testing forms.

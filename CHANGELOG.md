# Changelog

All notable changes to `javaabu/stencil` are documented in this file.

## Unreleased

Initial development of v1.0:

- A fluent schema: forms (inline or classes), layouts (Section, Grid, Group, Fieldset, Tabs, Wizard), display components (Alert, TextEntry) and fields for javaabu/forms' components.
- Closures on every prop, with arguments injected by name or type; declarative conditions checked in the browser and on the server; live fields re-evaluated through `Route::stencil()`.
- Model binding with old input, casts, enums and relationships; validation rules and attribute names from the schema; `validate()`, `fillModel()`, `save()` and relationship saving.
- Renderer drivers: Blade (javaabu/forms v2) and JSON (Inertia), and `Stencil::extend()` for your own.
- A React renderer (`StencilForm`, `StencilFields`, a component registry) built on @javaabu/forms and shipped through javaabu/manifest; a Blade script for wizards and live fields, prebuilt in `dist/`.
- Custom field types and `make:stencil-field`.
- Testing helpers: `AssertableForm`, `InteractsWithStencil`, and `RendererTestKit` for drivers.
- Optional javaabu/translatable support: per-locale inputs with `->translatable()`.
- English and Dhivehi strings, Thaana fields and RTL layouts.
- `statePath()` nests the fields inside a component under a key (`meta[subtitle]`, `meta.subtitle`); `ResolvesTranslatableFields` lets a record or form say which fields are translatable; `TextInput::isInteger()` and `Field::isDeclaredTranslatable()` (#16).
- `Form` is `Htmlable` but no longer `Stringable`: Blade cast it to a string when it was passed to a component attribute, rendering it there (and recursively when that component rendered the form). Use `{{ $form }}`, `toHtml()` or `render()` instead of `(string) $form` (#17).
- `@javaabu/forms`, `react` and `react-dom` are declared for Inertia pages only (manifest's `for: 'pages'`), so `manifest:check` doesn't ask Blade apps to install React.

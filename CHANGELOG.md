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

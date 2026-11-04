---
title: Configuration
sidebar_position: 1
---

```bash
php artisan vendor:publish --tag=stencil-config
```

| Key | Env | Default | What |
| --- | --- | --- | --- |
| `views` | `STENCIL_VIEWS` | `blade` | The driver `Stencil::render()` uses: `blade`, `inertia` (JSON), or one added with `Stencil::extend()` |
| `live.route` | | `stencil.evaluate` | The name of the route `Route::stencil()` registers and live forms post to |
| `live.controller` | | `EvaluateController` | The controller `Route::stencil()` uses. Extend `Javaabu\Stencil\Http\Controllers\EvaluateController` to change it |
| `live.debounce` | | `500` | Milliseconds a live text field waits after typing stops |
| `live.token_lifetime` | | `720` | Minutes a rendered live form can be re-evaluated; `null` for no limit |
| `submit` | | `true` | Whether forms render a submit button unless they say otherwise |
| `assets` | `STENCIL_ASSETS` | `true` | Whether the Blade driver requires stencil's stylesheet and script through javaabu/manifest. Turn it off to include your own |

# Assets

| Asset | When | What |
| --- | --- | --- |
| `stencil::styles` | every Blade form | Grids, spans, fieldsets, option groups, the wizard |
| `stencil::stencil` | forms with a wizard or live fields | Wizard steps and live updates |

For live forms, the Blade driver also requires javaabu/forms' scripts for every field type in the form, so a select that appears after the page loads still works. With manifest's Vite plugin they build with your app; without it, manifest serves the prebuilt files in the package's `dist/`. Place them yourself with `@manifestStyles('stencil')` and `@manifestScripts('stencil')`.

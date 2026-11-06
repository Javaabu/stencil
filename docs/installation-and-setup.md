---
title: Installation & Setup
sidebar_position: 1.2
---

# Installing

Stencil and its Javaabu dependencies aren't on Packagist yet. Add their repositories, then require the package:

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/Javaabu/stencil" },
    { "type": "vcs", "url": "https://github.com/Javaabu/forms" },
    { "type": "vcs", "url": "https://github.com/Javaabu/manifest" }
]
```

```bash
composer require javaabu/stencil:^1.0@dev
```

The service provider and the `Stencil` facade register themselves. Blade forms work straight away: `{{ $form }}` renders with javaabu/forms, and manifest adds stencil's stylesheet (and its script, when a form needs it) to the page.

# Live fields

Forms with [live fields](./basic-usage/live-fields.md) post their state to a route. Add it to `routes/web.php`, with the middleware your forms' pages use:

```php
Route::stencil();                                   // POST /stencil/evaluate, named stencil.evaluate
Route::stencil()->middleware('auth:admin');
Route::stencil('admin/stencil/evaluate');           // another URL
```

# Inertia React

The React renderer is in the Composer package, under `resources/js`, and your app builds it with its own Vite through [javaabu/manifest](https://github.com/Javaabu/manifest). Add manifest's Vite plugin if you haven't:

```js
// vite.config.js
import { defineConfig } from "vite"
import laravel from "laravel-vite-plugin"
import react from "@vitejs/plugin-react"
import manifest from "./vendor/javaabu/manifest/dist/vite.js"

export default defineConfig({
  plugins: [laravel({ input: ["resources/js/app.tsx"] }), react(), manifest()],
})
```

Install what the renderer imports. `php artisan manifest:check` lists it, with the command for your package manager, once your `package.json` has `@inertiajs/react` (or with `--scope=pages`); Blade-only apps aren't asked for these:

```bash
npm install github:Javaabu/react-forms#next-gen react react-dom
php artisan manifest:check
```

With pnpm 10, allow `@javaabu/forms` and `@javaabu/i18n` to build when installed from Git (see @javaabu/forms' installation docs). Then set up `FormsProvider` once, as for any @javaabu/forms page, and import from `@javaabu/stencil`:

```tsx
import { FormsProvider } from "@javaabu/forms"
import { inertia } from "@javaabu/forms/inertia"
import { onu } from "@javaabu/forms/onu"
import { StencilForm } from "@javaabu/stencil"

<FormsProvider ui={onu} router={inertia}>
  <StencilForm schema={form} />
</FormsProvider>
```

Run `php artisan manifest:types` to get TypeScript paths for `@javaabu/stencil`.

# Publishing

```bash
php artisan vendor:publish --tag=stencil-config        # config/stencil.php
php artisan vendor:publish --tag=stencil-translations  # lang/vendor/stencil
php artisan vendor:publish --tag=stencil-views         # resources/views/vendor/stencil
```

Nothing needs publishing for the common case. See [Configuration](./advanced-usage/configuration.md) and [Customizing views](./advanced-usage/customizing-views.md).

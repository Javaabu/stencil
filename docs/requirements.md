---
title: Requirements
sidebar_position: 1.1
---

This package requires:

- PHP 8.4 or higher
- Laravel 12 or 13
- [javaabu/forms](https://github.com/Javaabu/forms) v2, for the Blade components (installed with stencil)
- [javaabu/manifest](https://github.com/Javaabu/manifest), which ships stencil's CSS, JavaScript and React renderer to your app (installed with stencil)

For Inertia React pages (Blade apps need none of these, and `php artisan manifest:check` only asks for them when your app uses Inertia):

- React 19
- [@javaabu/forms](https://github.com/Javaabu/react-forms) and its peer dependencies (`php artisan manifest:check` lists what's missing)
- manifest's Vite plugin, which lets your app import `@javaabu/stencil`

Optional:

- [javaabu/translatable](https://github.com/Javaabu/translatable) v3, for [translatable fields](./advanced-usage/translatable.md)
- [spatie/laravel-medialibrary](https://github.com/spatie/laravel-medialibrary), for file fields that save to media collections

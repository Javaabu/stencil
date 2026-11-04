<?php

use Javaabu\Stencil\Http\Controllers\EvaluateController;

return [

    /*
    |--------------------------------------------------------------------------
    | Views
    |--------------------------------------------------------------------------
    |
    | Which driver Stencil::render() uses: 'blade' renders HTML with
    | javaabu/forms, 'inertia' serializes the schema to JSON for the React
    | renderer. Any driver added with Stencil::extend() works here too.
    |
    */

    'views' => env('STENCIL_VIEWS', 'blade'),

    /*
    |--------------------------------------------------------------------------
    | Live fields
    |--------------------------------------------------------------------------
    |
    | Fields marked ->live() send the form's state to the evaluation route when
    | they change, and the form re-renders with closures evaluated against it.
    | Register the route with Route::stencil() in your routes file.
    |
    */

    'live' => [
        'route' => 'stencil.evaluate',

        // The controller Route::stencil() registers. Extend EvaluateController to change it.
        'controller' => EvaluateController::class,

        // Milliseconds to wait after typing stops before a live text field updates the form.
        'debounce' => 500,

        // How long a rendered form can be re-evaluated, in minutes. null for no limit.
        'token_lifetime' => 720,
    ],

    /*
    |--------------------------------------------------------------------------
    | Submit button
    |--------------------------------------------------------------------------
    |
    | Whether forms render a submit button after their fields, unless the form
    | says otherwise with ->submit().
    |
    */

    'submit' => true,

    /*
    |--------------------------------------------------------------------------
    | Assets
    |--------------------------------------------------------------------------
    |
    | The Blade driver requires the stencil stylesheet and script through
    | javaabu/manifest. Set to false to include your own.
    |
    */

    'assets' => env('STENCIL_ASSETS', true),

];

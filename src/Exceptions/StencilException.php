<?php

namespace Javaabu\Stencil\Exceptions;

use Javaabu\Stencil\Components\Component;
use LogicException;

class StencilException extends LogicException
{
    public static function notInForm(Component $component): static
    {
        return new static(sprintf(
            'The [%s] component [%s] is not inside a form. Add it to a Form schema before rendering or evaluating it.',
            $component::class,
            $component->getKey(),
        ));
    }

    public static function duplicateField(string $name): static
    {
        return new static("The form has more than one field named [{$name}]. Field names must be unique.");
    }

    public static function unknownField(string $name): static
    {
        return new static("The form has no field named [{$name}].");
    }

    public static function liveNeedsFormClass(string $field): static
    {
        return new static(
            "The field [{$field}] is live, which needs the form to be a class so the server can rebuild it. "
            . 'Move the schema into a class that extends Javaabu\Stencil\Form and returns it from definition().',
        );
    }

    public static function liveRouteMissing(string $route): static
    {
        return new static("The form has live fields, but the [{$route}] route is not defined. Add Route::stencil() to your routes.");
    }

    public static function noModel(): static
    {
        return new static('The form has no record or model class. Call ->model($record) or ->model(Article::class) first.');
    }
}

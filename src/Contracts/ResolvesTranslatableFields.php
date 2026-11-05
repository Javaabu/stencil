<?php

namespace Javaabu\Stencil\Contracts;

/**
 * For a record (or a form class) that knows which of its fields are translatable
 * when it isn't a javaabu/translatable model itself, such as javaabu/meta's
 * per-locale meta values. Stencil asks it instead of Translatable::supports().
 */
interface ResolvesTranslatableFields
{
    /**
     * @param  string  $state_path  the field's full state path, in dot notation: meta.subtitle
     */
    public function isTranslatableField(string $state_path): bool;
}

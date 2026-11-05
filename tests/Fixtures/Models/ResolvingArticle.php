<?php

namespace Javaabu\Stencil\Tests\Fixtures\Models;

use Javaabu\Stencil\Contracts\ResolvesTranslatableFields;

/**
 * Keeps per-locale values in its address JSON (address.line_dv), the way
 * javaabu/meta keeps translatable meta values.
 */
class ResolvingArticle extends Article implements ResolvesTranslatableFields
{
    protected $table = 'articles';

    public function isTranslatableField(string $state_path): bool
    {
        return $state_path === 'address.line';
    }
}

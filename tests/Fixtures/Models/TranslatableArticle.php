<?php

namespace Javaabu\Stencil\Tests\Fixtures\Models;

use Javaabu\Translatable\Contracts\JsonTranslatable;
use Javaabu\Translatable\JsonTranslatable\IsJsonTranslatable;

class TranslatableArticle extends Article implements JsonTranslatable
{
    use IsJsonTranslatable;

    protected $table = 'articles';

    protected array $translatable = ['title', 'body'];

    public function getMorphClass(): string
    {
        return Article::class;
    }
}

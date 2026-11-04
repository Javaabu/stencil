<?php

namespace Javaabu\Stencil\Tests\Fixtures\Enums;

enum ArticleStatuses: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'ޑްރާފްޓް',
            self::PUBLISHED => 'ޕަބްލިޝްކުރެވިފައި',
        };
    }
}

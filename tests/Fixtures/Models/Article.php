<?php

namespace Javaabu\Stencil\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Javaabu\Stencil\Tests\Fixtures\Enums\ArticleStatuses;

class Article extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'body',
        'status',
        'published_at',
        'category_id',
        'is_featured',
        'channels',
        'type',
        'company_name',
        'address',
    ];

    protected function casts(): array
    {
        return [
            'status' => ArticleStatuses::class,
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
            'channels' => 'array',
            'address' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }
}

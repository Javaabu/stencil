<?php

namespace Javaabu\Stencil\Tests\Fixtures\Forms;

use Illuminate\Support\Str;
use Javaabu\Stencil\Components\Section;
use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\DateTimePicker;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Fields\Textarea;
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Stencil\Form;
use Javaabu\Stencil\Get;
use Javaabu\Stencil\Set;
use Javaabu\Stencil\Tests\Fixtures\Enums\ArticleStatuses;

class ArticleForm extends Form
{
    protected function definition(): array
    {
        return [
            Section::make(__('Article'))->columns(2)->schema([
                TextInput::make('title')
                    ->label('ސުރުހީ')
                    ->required()
                    ->maxLength(255)
                    ->thaana()
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')->required()->unique(),
                Select::make('status')->options(ArticleStatuses::class)->required()->live(),
                DateTimePicker::make('published_at')->visible(fn (Get $get) => $get('status') === 'published'),
                Select::make('category_id')->relationship('category', 'name')->label('ބައި'),
                Select::make('tags')->multiple()->relationship('tags', 'name'),
                Checkbox::make('is_featured'),
                Textarea::make('body')->thaana()->columnSpanFull(),
            ]),
        ];
    }
}

<?php

namespace Javaabu\Stencil\Tests\Unit;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Fields\Field;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Stencil\Form;
use Javaabu\Stencil\Get;
use Javaabu\Stencil\Set;
use Javaabu\Stencil\Tests\Fixtures\Models\Article;
use Javaabu\Stencil\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ClosureEvaluationTest extends TestCase
{
    #[Test]
    public function it_injects_arguments_by_name(): void
    {
        $article = new Article(['title' => 'ސުރުހީ', 'type' => 'company']);
        $seen = [];

        $form = Form::make()->model($article)->schema([
            TextInput::make('title')->label(function ($get, $set, $state, $record, $model, $operation, $component, $form) use (&$seen) {
                $seen = compact('get', 'set', 'state', 'record', 'model', 'operation', 'component', 'form');

                return 'ސުރުހީ';
            }),
            TextInput::make('type'),
        ]);

        $form->getField('title')->getLabel();

        $this->assertInstanceOf(Get::class, $seen['get']);
        $this->assertInstanceOf(Set::class, $seen['set']);
        $this->assertEquals('ސުރުހީ', $seen['state']);
        $this->assertSame($article, $seen['record']);
        $this->assertEquals(Article::class, $seen['model']);
        $this->assertEquals('create', $seen['operation']);
        $this->assertSame($form->getField('title'), $seen['component']);
        $this->assertSame($form, $seen['form']);
        $this->assertEquals('company', ($seen['get'])('type'));
    }

    #[Test]
    public function it_injects_arguments_by_type(): void
    {
        $article = new Article(['title' => 'ސުރުހީ']);
        $seen = [];

        $form = Form::make()->model($article)->schema([
            TextInput::make('title')->label(function (Get $read, Set $write, Article $article, Field $field, Component $any, Form $root, Request $request) use (&$seen) {
                $seen = compact('read', 'write', 'article', 'field', 'any', 'root', 'request');

                return 'x';
            }),
        ]);

        $form->getField('title')->getLabel();

        $this->assertInstanceOf(Get::class, $seen['read']);
        $this->assertInstanceOf(Set::class, $seen['write']);
        $this->assertSame($article, $seen['article']);
        $this->assertSame($form->getField('title'), $seen['field']);
        $this->assertSame($form, $seen['root']);
        $this->assertInstanceOf(Request::class, $seen['request']);
    }

    #[Test]
    public function it_passes_null_for_a_nullable_record_that_is_missing(): void
    {
        $form = Form::make()->schema([
            TextInput::make('title')->label(fn (?Article $record) => $record === null ? 'new' : 'old'),
        ]);

        $this->assertEquals('new', $form->getField('title')->getLabel());
    }

    #[Test]
    public function it_explains_what_it_cannot_resolve(): void
    {
        $form = Form::make()->schema([
            TextInput::make('title')->label(fn ($nonsense) => 'x'),
        ]);

        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('[$nonsense]');

        $form->getField('title')->getLabel();
    }

    #[Test]
    public function it_evaluates_props_against_the_current_state(): void
    {
        $form = Form::make()->schema([
            Select::make('island')->options(['male' => 'މާލެ', 'hulhumale' => 'ހުޅުމާލެ']),
            Select::make('ward')->options(fn (Get $get) => match ($get('island')) {
                'male' => ['henveyru' => 'ހެންވޭރު', 'galolhu' => 'ގަލޮޅު'],
                default => [],
            })->required(fn (Get $get) => filled($get('island'))),
        ]);

        $form->fill(['island' => 'male']);

        $this->assertEquals(['henveyru' => 'ހެންވޭރު', 'galolhu' => 'ގަލޮޅު'], $form->getField('ward')->getOptions());
        $this->assertTrue($form->getField('ward')->isRequired());

        $form->fill(['island' => null]);

        $this->assertEquals([], $form->getField('ward')->getOptions());
        $this->assertFalse($form->getField('ward')->isRequired());
    }

    #[Test]
    public function it_evaluates_child_components_from_a_closure(): void
    {
        $form = Form::make()
            ->model(['type' => 'company'])
            ->schema(fn (Get $get) => [
                TextInput::make('type'),
                $get('type') === 'company' ? TextInput::make('company_name') : null,
            ]);

        $this->assertTrue($form->hasField('company_name'));
    }
}

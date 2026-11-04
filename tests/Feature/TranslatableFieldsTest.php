<?php

namespace Javaabu\Stencil\Tests\Feature;

use Javaabu\Stencil\Fields\Textarea;
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Stencil\Form;
use Javaabu\Stencil\Tests\Fixtures\Models\Article;
use Javaabu\Stencil\Tests\Fixtures\Models\TranslatableArticle;
use Javaabu\Stencil\Tests\TestCase;
use Javaabu\Translatable\Testing\InteractsWithLanguages;
use Javaabu\Translatable\Translatable;
use PHPUnit\Framework\Attributes\Test;

/**
 * Runs with javaabu/translatable installed; skipped in the CI leg without it.
 */
class TranslatableFieldsTest extends TestCase
{
    protected object $languages;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(Translatable::class)) {
            $this->markTestSkipped('javaabu/translatable is not installed.');
        }

        // An anonymous class, so this file loads without the package too.
        $this->languages = new class
        {
            use InteractsWithLanguages {
                setUpLanguages as public;
                withoutTranslatable as public;
            }
        };

        $this->setUpDatabase();
        $this->languages->setUpLanguages(['en', 'dv']);
    }

    protected function form(mixed $model): Form
    {
        return Form::make()->model($model)->schema([
            TextInput::make('title')->label('Title')->required()->maxLength(255)->translatable(),
            Textarea::make('body')->translatable(layout: 'stacked'),
            TextInput::make('slug'),
        ]);
    }

    protected function article(): TranslatableArticle
    {
        $article = TranslatableArticle::create(['title' => 'Welcome', 'slug' => 'welcome', 'lang' => 'en']);
        $article->title_dv = 'މަރުޙަބާ';
        $article->save();

        return $article->fresh();
    }

    #[Test]
    public function it_fills_one_input_per_locale(): void
    {
        $state = $this->form($this->article())->getState();

        $this->assertEquals('Welcome', $state['title']);
        $this->assertEquals('Welcome', $state['title_en']);
        $this->assertEquals('މަރުޙަބާ', $state['title_dv']);
        $this->assertNull($state['body_dv']);
        $this->assertArrayNotHasKey('slug_en', $state);
    }

    #[Test]
    public function it_validates_each_input(): void
    {
        $form = $this->form(TranslatableArticle::class);

        $rules = $form->fill([])->rules();

        $this->assertEquals(['required', 'string', 'max:255'], $rules['title_en']);
        $this->assertEquals(['nullable', 'string', 'max:255'], $rules['title_dv']);
        $this->assertArrayNotHasKey('title', $rules);
        $this->assertArrayHasKey('slug', $rules);
        $this->assertStringContainsString('ދިވެހި', $form->validationAttributes()['title_dv']);
    }

    #[Test]
    public function it_saves_the_translations(): void
    {
        $form = $this->form(TranslatableArticle::class);

        $data = $form->validate(['title_en' => 'Welcome', 'title_dv' => 'މަރުޙަބާ', 'body_en' => null, 'body_dv' => 'ތަފްޞީލް', 'slug' => 'welcome']);

        $this->assertEquals(['title_en' => 'Welcome', 'title_dv' => 'މަރުޙަބާ', 'body_en' => null, 'body_dv' => 'ތަފްޞީލް', 'slug' => 'welcome'], $data);

        $article = $form->save($data)->fresh();

        $this->assertEquals('Welcome', $article->title);
        $this->assertEquals('މަރުޙަބާ', $article->title_dv);
        $this->assertEquals('ތަފްޞީލް', $article->body_dv);
    }

    #[Test]
    public function it_serializes_the_inputs_for_js_drivers(): void
    {
        $node = $this->form($this->article())->toArray()['components'][0];

        $this->assertEquals('tabs', $node['translatable']['layout']);
        $this->assertEquals(['title_en', 'title_dv'], array_column($node['translatable']['inputs'], 'name'));
        $this->assertEquals('rtl', $node['translatable']['inputs'][1]['direction']);
        $this->assertTrue($node['translatable']['inputs'][1]['thaana']);
        $this->assertTrue($node['translatable']['inputs'][0]['primary']);
    }

    #[Test]
    public function it_renders_the_inputs_with_blade(): void
    {
        $html = $this->form($this->article())->toHtml();

        $this->assertStringContainsString('name="title_en"', $html);
        $this->assertMatchesRegularExpression('/dir="rtl"\s+lang="dv"[^>]*data-thaana/', $html);
        $this->assertStringContainsString('value="މަރުޙަބާ"', $html);
        $this->assertStringContainsString('data-stencil-translatable="tabs"', $html);
        $this->assertStringContainsString('data-stencil-translatable="stacked"', $html);
        $this->assertStringContainsString('name="body_dv"', $html);
        $this->assertStringNotContainsString('name="title"', $html);
    }

    #[Test]
    public function it_is_a_plain_field_for_a_model_that_isnt_translatable(): void
    {
        $form = $this->form(Article::class);

        $this->assertArrayHasKey('title', $form->fill([])->rules());
        $this->assertStringContainsString('name="title"', $form->toHtml());
        $this->assertArrayNotHasKey('translatable', $form->toArray()['components'][0]);
    }

    #[Test]
    public function it_is_a_plain_field_with_translations_off(): void
    {
        $this->languages->withoutTranslatable(function () {
            $form = $this->form(TranslatableArticle::class);

            $this->assertEquals(['title' => 'Welcome', 'body' => null, 'slug' => null], $form->validate(['title' => 'Welcome']));
            $this->assertStringContainsString('name="title"', $form->toHtml());
        });
    }
}

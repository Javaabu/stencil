<?php

namespace Javaabu\Stencil\Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\CheckboxList;
use Javaabu\Stencil\Fields\DatePicker;
use Javaabu\Stencil\Fields\DateTimePicker;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Stencil\Fields\TimePicker;
use Javaabu\Stencil\Form;
use Javaabu\Stencil\Tests\Fixtures\Enums\ArticleStatuses;
use Javaabu\Stencil\Tests\Fixtures\Forms\ArticleForm;
use Javaabu\Stencil\Tests\Fixtures\Models\Article;
use Javaabu\Stencil\Tests\Fixtures\Models\Category;
use Javaabu\Stencil\Tests\Fixtures\Models\Tag;
use Javaabu\Stencil\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ModelBindingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function article(): Article
    {
        $category = Category::create(['name' => 'ޚަބަރު']);

        $article = Article::create([
            'title' => 'ރާއްޖޭގެ ޚަބަރު',
            'slug' => 'raajjeyge-khabaru',
            'status' => ArticleStatuses::PUBLISHED,
            'published_at' => Carbon::parse('2026-11-04 09:30:00'),
            'category_id' => $category->id,
            'is_featured' => true,
            'channels' => ['sms'],
            'address' => ['city' => 'މާލެ'],
        ]);

        $article->tags()->attach([Tag::create(['name' => 'ސިޔާސީ'])->id, Tag::create(['name' => 'ވިޔަފާރި'])->id]);

        return $article;
    }

    #[Test]
    public function it_fills_the_state_from_a_model_with_casts_and_relationships(): void
    {
        $article = $this->article();

        $form = ArticleForm::make()->model($article);

        $this->assertEquals('edit', $form->getOperation());
        $this->assertEquals('PATCH', $form->getMethod());

        $state = $form->getState();

        $this->assertEquals('ރާއްޖޭގެ ޚަބަރު', $state['title']);
        $this->assertEquals('published', $state['status']);
        $this->assertEquals('2026-11-04 09:30', $state['published_at']);
        $this->assertEquals($article->category_id, $state['category_id']);
        $this->assertEquals($article->tags->modelKeys(), $state['tags']);
        $this->assertTrue($state['is_featured']);
    }

    #[Test]
    public function it_fills_nested_names_arrays_and_date_formats(): void
    {
        $form = Form::make()
            ->model([
                'address' => ['city' => 'މާލެ'],
                'channels' => collect(['sms', 'email']),
                'starts_on' => Carbon::parse('2026-01-15 08:00'),
                'starts_at' => Carbon::parse('2026-01-15 08:00'),
            ])
            ->schema([
                TextInput::make('address.city'),
                CheckboxList::make('channels')->options(['sms' => 'SMS', 'email' => 'Email']),
                DatePicker::make('starts_on'),
                TimePicker::make('starts_at'),
            ]);

        $this->assertEquals([
            'address' => ['city' => 'މާލެ'],
            'channels' => ['sms', 'email'],
            'starts_on' => '2026-01-15',
            'starts_at' => '08:00',
        ], $form->getState());
    }

    #[Test]
    public function it_uses_defaults_when_the_record_has_no_value(): void
    {
        $form = Form::make()->model(Article::class)->schema([
            TextInput::make('title')->default('ސުރުހީ'),
            Select::make('status')->options(ArticleStatuses::class)->default(ArticleStatuses::DRAFT),
            TextInput::make('slug')->default(fn () => 'from-closure'),
            Checkbox::make('is_featured')->default(true),
        ]);

        $this->assertEquals('create', $form->getOperation());
        $this->assertEquals('POST', $form->getMethod());
        $this->assertEquals(['title' => 'ސުރުހީ', 'status' => 'draft', 'slug' => 'from-closure', 'is_featured' => true], $form->getState());
    }

    #[Test]
    public function it_prefers_old_input_like_javaabu_forms(): void
    {
        $this->app['session.store']->flashInput(['title' => 'އާ ސުރުހީ', 'address' => ['city' => 'އައްޑޫ']]);
        $this->app['request']->setLaravelSession($this->app['session.store']);

        $form = Form::make()
            ->model(['title' => 'ސުރުހީ', 'is_featured' => true, 'address' => ['city' => 'މާލެ']])
            ->schema([
                TextInput::make('title'),
                TextInput::make('address.city'),
                Checkbox::make('is_featured'),
            ]);

        $this->assertEquals('އާ ސުރުހީ', $form->getStateValue('title'));
        $this->assertEquals('އައްޑޫ', $form->getStateValue('address[city]'));
        $this->assertNull($form->getStateValue('is_featured'));
    }

    #[Test]
    public function it_formats_state_from_the_record(): void
    {
        $form = Form::make()
            ->model(['price' => 1050])
            ->schema([
                TextInput::make('price')->numeric()
                    ->formatStateUsing(fn (?int $state) => $state / 100)
                    ->dehydrateStateUsing(fn ($state) => (int) round($state * 100)),
            ]);

        $this->assertEquals(10.5, $form->getStateValue('price'));
        $this->assertEquals(['price' => 1275], $form->validate(['price' => '12.75']));
    }

    #[Test]
    public function it_does_not_fill_passwords(): void
    {
        $form = Form::make()->model(['password' => 'secret'])->schema([TextInput::make('password')->password()]);

        $this->assertNull($form->getStateValue('password'));
    }

    #[Test]
    public function it_derives_rules_and_attribute_names_from_the_schema(): void
    {
        $this->article();

        $form = ArticleForm::make()->model(Article::class)->fill(['status' => 'draft']);

        $rules = $form->rules();

        $this->assertEquals(['required', 'string', 'max:255'], $rules['title']);
        $this->assertEquals('required', $rules['slug'][0]);
        $this->assertEquals('in:"draft","published"', (string) $rules['status'][1]);
        $this->assertArrayNotHasKey('published_at', $rules);
        $this->assertEquals(['nullable', 'array'], $rules['tags']);
        $this->assertArrayHasKey('tags.*', $rules);
        $this->assertEquals(['nullable', 'boolean'], $rules['is_featured']);

        $this->assertEquals('ސުރުހީ', $form->validationAttributes()['title']);
        $this->assertEquals('ބައި', $form->validationAttributes()['category_id']);
    }

    #[Test]
    public function it_validates_with_messages_in_dhivehi(): void
    {
        $form = Form::make()->schema([
            TextInput::make('title')->label('ސުރުހީ')->required()->validationMessages(['required' => ':attribute ލިޔުއްވާ']),
            TextInput::make('email')->email(),
        ]);

        try {
            $form->validate(['title' => '', 'email' => 'not-an-email']);
            $this->fail('Validation passed.');
        } catch (ValidationException $exception) {
            $this->assertEquals('ސުރުހީ ލިޔުއްވާ', $exception->errors()['title'][0]);
            $this->assertArrayHasKey('email', $exception->errors());
        }
    }

    #[Test]
    public function it_returns_only_the_fields_that_are_dehydrated(): void
    {
        $form = Form::make()->schema([
            Select::make('type')->options(['person' => 'ފަރުދެއް', 'company' => 'ކުންފުންޏެއް']),
            TextInput::make('company_name')->visibleWhen('type', 'company'),
            TextInput::make('id_card')->visibleWhen('type', 'person'),
            TextInput::make('internal')->hidden(),
            TextInput::make('code')->disabled(),
            TextInput::make('password')->password(),
            TextInput::make('password_confirmation')->password()->dehydrated(false),
            Checkbox::make('agree'),
            CheckboxList::make('channels')->options(['sms' => 'SMS']),
        ]);

        $data = $form->validate([
            'type' => 'company',
            'company_name' => 'ޖާވާބު',
            'id_card' => 'A123456',
            'internal' => 'x',
            'code' => 'y',
            'password' => 'secret',
            'password_confirmation' => 'secret',
            'unknown' => 'z',
        ]);

        $this->assertEquals([
            'type' => 'company',
            'company_name' => 'ޖާވާބު',
            'password' => 'secret',
            'agree' => false,
            'channels' => [],
        ], $data);
    }

    #[Test]
    public function it_creates_a_model_with_its_relationships(): void
    {
        $category = Category::create(['name' => 'ޚަބަރު']);
        $tag = Tag::create(['name' => 'ސިޔާސީ']);

        $article = ArticleForm::make()->model(Article::class)->save(ArticleForm::make()->model(Article::class)->validate([
            'title' => 'ރާއްޖޭގެ ޚަބަރު',
            'slug' => 'raajje',
            'status' => 'published',
            'published_at' => '2026-11-04 10:00',
            'category_id' => (string) $category->id,
            'tags' => [(string) $tag->id],
            'is_featured' => '1',
            'body' => 'ތަފްޞީލް',
        ]));

        $this->assertTrue($article->exists);
        $this->assertDatabaseHas('articles', [
            'title' => 'ރާއްޖޭގެ ޚަބަރު',
            'status' => 'published',
            'category_id' => $category->id,
            'is_featured' => true,
        ]);
        $this->assertDatabaseHas('article_tag', ['article_id' => $article->id, 'tag_id' => $tag->id]);
    }

    #[Test]
    public function it_updates_a_model_and_syncs_its_relationships(): void
    {
        $article = $this->article();
        $tag = Tag::create(['name' => 'ކުޅިވަރު']);

        $form = ArticleForm::make()->model($article);

        $form->save($form->validate([
            'title' => 'އާ ސުރުހީ',
            'slug' => 'raajjeyge-khabaru',
            'status' => 'draft',
            'published_at' => '2027-01-01 00:00',
            'tags' => [(string) $tag->id],
        ]));

        $article->refresh();

        $this->assertEquals('އާ ސުރުހީ', $article->title);
        $this->assertEquals(ArticleStatuses::DRAFT, $article->status);
        $this->assertEquals('2026-11-04 09:30', $article->published_at->format('Y-m-d H:i'));
        $this->assertNull($article->category_id);
        $this->assertFalse($article->is_featured);
        $this->assertEquals([$tag->id], $article->tags()->pluck('tags.id')->all());
    }

    #[Test]
    public function it_ignores_the_record_in_unique_rules(): void
    {
        $article = $this->article();

        $form = ArticleForm::make()->model($article);

        $form->validate(['title' => 'ސުރުހީ', 'slug' => 'raajjeyge-khabaru', 'status' => 'draft']);

        $this->expectException(ValidationException::class);

        ArticleForm::make()->model(Article::class)->validate(['title' => 'ސުރުހީ', 'slug' => 'raajjeyge-khabaru', 'status' => 'draft']);
    }

    #[Test]
    public function it_validates_a_request_into_a_named_error_bag(): void
    {
        $form = Form::make()->errorBag('profile')->schema([TextInput::make('name')->required()]);

        try {
            $form->validate([]);
            $this->fail('Validation passed.');
        } catch (ValidationException $exception) {
            $this->assertEquals('profile', $exception->errorBag);
        }
    }

    #[Test]
    public function it_casts_numbers_and_checks_dates(): void
    {
        $form = Form::make()->schema([
            TextInput::make('quantity')->integer()->minValue(1)->maxValue(10),
            DateTimePicker::make('starts_at')->minDate('2026-01-01'),
        ]);

        $this->assertEquals(['nullable', 'integer', 'min:1', 'max:10'], $form->rules()['quantity']);
        $this->assertEquals(['nullable', 'date', 'after_or_equal:2026-01-01 00:00'], $form->rules()['starts_at']);
        $this->assertSame(['quantity' => 3, 'starts_at' => '2026-02-01 10:00'], $form->validate(['quantity' => '3', 'starts_at' => '2026-02-01 10:00']));
    }
}

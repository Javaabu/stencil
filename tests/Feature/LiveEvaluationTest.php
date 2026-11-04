<?php

namespace Javaabu\Stencil\Tests\Feature;

use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Foundation\Auth\User;
use Javaabu\Stencil\Http\LiveToken;
use Javaabu\Stencil\Tests\Fixtures\Forms\ArticleForm;
use Javaabu\Stencil\Tests\Fixtures\Models\Article;
use Javaabu\Stencil\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class LiveEvaluationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function defineRoutes($router): void
    {
        /** @var Registrar $router */
        $router->stencil();
    }

    protected function token(?Article $article = null): string
    {
        return ArticleForm::make()->model($article ?? Article::class)->toArray()['live']['token'];
    }

    #[Test]
    public function it_re_evaluates_the_schema_with_the_submitted_state(): void
    {
        $response = $this->postJson('/stencil/evaluate', [
            '_stencil' => $this->token(),
            '_stencil_changed' => 'status',
            'title' => 'ސުރުހީ',
            'status' => 'published',
        ]);

        $response->assertOk();

        $published_at = collect($response->json('schema.components.0.children'))->firstWhere('key', 'published_at');

        $this->assertArrayNotHasKey('hidden', $published_at);
        $this->assertEquals('published', $response->json('state.status'));
        $this->assertEquals([], $response->json('changed'));
    }

    #[Test]
    public function it_runs_after_state_updated_with_set(): void
    {
        $response = $this->postJson('/stencil/evaluate', [
            '_stencil' => $this->token(),
            '_stencil_changed' => 'title',
            'title' => 'Hello World',
        ]);

        $response->assertOk()
            ->assertJsonPath('changed', ['slug'])
            ->assertJsonPath('state.slug', 'hello-world')
            ->assertJsonPath('schema.state.slug', 'hello-world');
    }

    #[Test]
    public function it_renders_html_for_the_blade_driver(): void
    {
        $response = $this->postJson('/stencil/evaluate', [
            '_stencil' => $this->token(),
            '_stencil_driver' => 'blade',
            '_stencil_changed' => 'status',
            'status' => 'published',
        ]);

        $response->assertOk();

        $this->assertStringContainsString('name="published_at"', $response->json('html'));
        $this->assertArrayNotHasKey('schema', $response->json());
    }

    #[Test]
    public function it_keeps_the_record_of_the_rendered_form(): void
    {
        $article = Article::create(['title' => 'ސުރުހީ', 'slug' => 'taken']);

        $response = $this->postJson('/stencil/evaluate', [
            '_stencil' => $this->token($article),
            'title' => 'ސުރުހީ',
            'slug' => 'taken',
        ]);

        $response->assertOk()
            ->assertJsonPath('schema.operation', 'edit')
            ->assertJsonPath('schema.method', 'PATCH');
    }

    #[Test]
    public function it_refuses_invalid_tokens(): void
    {
        $this->postJson('/stencil/evaluate', ['_stencil' => 'nonsense'])->assertForbidden();
        $this->postJson('/stencil/evaluate', [])->assertForbidden();
    }

    #[Test]
    public function it_refuses_tokens_of_another_user(): void
    {
        $token = $this->token();

        $this->actingAs((new User)->forceFill(['id' => 5]));

        $this->postJson('/stencil/evaluate', ['_stencil' => $token])->assertForbidden();
    }

    #[Test]
    public function it_refuses_expired_tokens(): void
    {
        config(['stencil.live.token_lifetime' => 1]);

        $token = $this->token();

        $this->travel(2)->minutes();

        $this->postJson('/stencil/evaluate', ['_stencil' => $token])->assertForbidden();
    }

    #[Test]
    public function it_asks_the_form_to_authorize(): void
    {
        $token = LiveToken::issue(DeniedForm::make()->model(Article::class));

        $this->postJson('/stencil/evaluate', ['_stencil' => $token])->assertForbidden();
    }
}

class DeniedForm extends ArticleForm
{
    public function authorize(): bool
    {
        return false;
    }
}

<?php

namespace Javaabu\Stencil\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Javaabu\Forms\FormsServiceProvider;
use Javaabu\Helpers\HelpersServiceProvider;
use Javaabu\Manifest\ManifestServiceProvider;
use Javaabu\Stencil\StencilServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            HelpersServiceProvider::class,
            ManifestServiceProvider::class,
            FormsServiceProvider::class,
            StencilServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:yWa/ByhLC/GUvfToOuaPD7zDwB64qkc/QkaQOrT5IpE=');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * Tables for the fixture models: articles, categories, tags.
     */
    protected function setUpDatabase(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->text('body')->nullable();
            $table->string('status')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->foreignId('category_id')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->json('channels')->nullable();
            $table->string('type')->nullable();
            $table->string('company_name')->nullable();
            $table->json('address')->nullable();
            $table->timestamps();
        });

        Schema::create('article_tag', function (Blueprint $table) {
            $table->foreignId('article_id');
            $table->foreignId('tag_id');
        });
    }
}

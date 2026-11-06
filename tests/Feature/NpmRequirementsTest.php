<?php

namespace Javaabu\Stencil\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Javaabu\Manifest\Enums\NpmScopes;
use Javaabu\Manifest\Facades\Manifest;
use Javaabu\Stencil\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class NpmRequirementsTest extends TestCase
{
    protected function tearDown(): void
    {
        File::delete(base_path('package.json'));
        File::deleteDirectory(base_path('node_modules'));

        parent::tearDown();
    }

    /**
     * An app with these npm packages in its package.json and node_modules.
     *
     * @param  array<string, string>  $installed
     */
    protected function app(array $installed): void
    {
        File::put(base_path('package.json'), json_encode(['devDependencies' => $installed]));

        foreach ($installed as $name => $version) {
            File::ensureDirectoryExists(base_path("node_modules/{$name}"));
            File::put(base_path("node_modules/{$name}/package.json"), json_encode(['name' => $name, 'version' => $version]));
        }
    }

    /**
     * manifest:check's rows that stencil asks for, by npm package name.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function stencilRequirements(): array
    {
        Artisan::call('manifest:check', ['--json' => true]);

        return collect(json_decode(Artisan::output(), true))
            ->filter(fn (array $row) => in_array('stencil', $row['needed_by'], true))
            ->keyBy('name')
            ->all();
    }

    #[Test]
    public function it_asks_blade_apps_for_no_react_packages(): void
    {
        $this->app(['vite' => '8.3.5', 'laravel-vite-plugin' => '3.2.0']);

        $requirements = $this->stencilRequirements();

        $this->assertEqualsCanonicalizing(['vite', 'laravel-vite-plugin'], array_keys($requirements));
        $this->assertEquals(['ok'], array_values(array_unique(array_column($requirements, 'status'))));
    }

    #[Test]
    public function it_asks_inertia_apps_for_the_react_renderers_packages(): void
    {
        $this->app([
            '@inertiajs/react' => '2.3.0',
            'laravel-vite-plugin' => '3.2.0',
            'react' => '19.3.1',
            'react-dom' => '19.3.1',
            'vite' => '8.3.5',
        ]);

        $requirements = $this->stencilRequirements();

        $this->assertEquals('missing', $requirements['@javaabu/forms']['status']);
        $this->assertEquals('ok', $requirements['react']['status']);
        $this->assertEquals('ok', $requirements['react-dom']['status']);
    }

    #[Test]
    public function it_declares_its_npm_packages_for_pages_only(): void
    {
        $package = Manifest::packages()['stencil'];

        $this->assertEquals([], $package->npmDependencies(NpmScopes::ASSETS));
        $this->assertEquals([], $package->npmPeerDependencies(NpmScopes::ASSETS));
        $this->assertArrayHasKey('@javaabu/forms', $package->npmDependencies(NpmScopes::PAGES));
        $this->assertEquals(['react', 'react-dom'], array_keys($package->npmPeerDependencies(NpmScopes::PAGES)));
    }
}

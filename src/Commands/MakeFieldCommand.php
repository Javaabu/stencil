<?php

namespace Javaabu\Stencil\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeFieldCommand extends Command
{
    protected $signature = 'make:stencil-field
        {name : The field class, like Rating}
        {--force : Overwrite files that exist}';

    protected $description = 'Create a custom Stencil field: a PHP class, a Blade view and a React component';

    public function handle(Filesystem $files): int
    {
        $class = Str::studly(class_basename((string) $this->argument('name')));
        $type = Str::kebab($class);
        $view = "stencil.fields.{$type}";

        $targets = [
            app_path("Stencil/Fields/{$class}.php") => 'field.php.stub',
            resource_path("views/stencil/fields/{$type}.blade.php") => 'field.blade.php.stub',
            resource_path("js/stencil/fields/{$class}.tsx") => 'field.tsx.stub',
        ];

        $replacements = [
            '{{ namespace }}' => $this->laravel->getNamespace() . 'Stencil\\Fields',
            '{{ class }}' => $class,
            '{{ type }}' => $type,
            '{{ view }}' => $view,
        ];

        foreach ($targets as $path => $stub) {
            if ($files->exists($path) && ! $this->option('force')) {
                $this->components->warn(__('stencil::strings.command_exists', ['path' => $path]));

                continue;
            }

            $files->ensureDirectoryExists(dirname($path));
            $files->put($path, strtr($files->get(__DIR__ . "/../../stubs/{$stub}"), $replacements));

            $this->components->info(__('stencil::strings.command_created', ['path' => $path]));
        }

        $this->components->bulletList([
            __('stencil::strings.command_use', ['class' => $class]),
            __('stencil::strings.command_register', ['type' => $type, 'class' => $class]),
        ]);

        return self::SUCCESS;
    }
}

<?php

namespace Javaabu\Stencil\Fields;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Javaabu\Stencil\Contracts\SavesRelationships;

/**
 * A file input. With collection(), the file is added to a spatie/laravel-medialibrary
 * collection of the model; otherwise it's stored on a disk and the path saved.
 */
class FileUpload extends Field implements SavesRelationships
{
    protected string $type = 'file';

    protected ?string $component = 'file';

    /**
     * javaabu/helpers' AllowedMimeTypes type: document, image, video, audio, …
     */
    protected string|array|Closure $file_type = 'document';

    protected array|string|Closure|null $mimetypes = null;

    protected int|Closure|null $max_size = null;

    protected string|Closure|null $collection = null;

    protected string|Closure|null $disk = null;

    protected string|Closure $directory = '';

    protected bool $is_multiple = false;

    /**
     * The kind of file, from javaabu/helpers' AllowedMimeTypes: document, image, video, audio, ….
     *
     * @param  string|array<int, string>|Closure  $type
     */
    public function fileType(string|array|Closure $type): static
    {
        $this->file_type = $type;

        return $this;
    }

    /**
     * Accept only these mime types.
     *
     * @param  array<int, string>|string|Closure|null  $mimetypes
     */
    public function acceptedFileTypes(array|string|Closure|null $mimetypes): static
    {
        $this->mimetypes = $mimetypes;

        return $this;
    }

    /**
     * The largest file accepted, in kilobytes.
     */
    public function maxSize(int|Closure|null $kilobytes): static
    {
        $this->max_size = $kilobytes;

        return $this;
    }

    /**
     * Save the file to this spatie/laravel-medialibrary collection of the model.
     */
    public function collection(string|Closure|null $collection): static
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * Where files are stored when there's no media collection.
     */
    public function disk(string|Closure|null $disk): static
    {
        $this->disk = $disk;

        return $this;
    }

    public function directory(string|Closure $directory): static
    {
        $this->directory = $directory;

        return $this;
    }

    public function multiple(bool $condition = true): static
    {
        $this->is_multiple = $condition;

        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->is_multiple;
    }

    public function getCollection(): ?string
    {
        return $this->evaluate($this->collection);
    }

    public function getInputName(): string
    {
        return parent::getInputName() . ($this->is_multiple ? '[]' : '');
    }

    public function savesToAttribute(): bool
    {
        return $this->getCollection() === null;
    }

    public function isDehydrated(): bool
    {
        if (! parent::isDehydrated()) {
            return false;
        }

        $state = $this->getState();

        // Nothing uploaded: keep what the model has.
        return $state instanceof UploadedFile || (is_array($state) && Arr::first($state) instanceof UploadedFile);
    }

    protected function readFromRecord(mixed $record): mixed
    {
        $collection = $this->getCollection();

        if ($collection === null || ! $record instanceof Model || ! method_exists($record, 'getFirstMediaUrl')) {
            return parent::readFromRecord($record);
        }

        if ($this->is_multiple) {
            return $record->getMedia($collection)->map(fn ($media) => $media->getUrl())->all();
        }

        return $record->getFirstMediaUrl($collection) ?: null;
    }

    public function normalizeState(mixed $state): mixed
    {
        if ($state instanceof UploadedFile) {
            return $state;
        }

        if (is_array($state) && Arr::first($state) instanceof UploadedFile) {
            return $state;
        }

        return parent::normalizeState($state);
    }

    protected function castDehydratedState(mixed $state): mixed
    {
        if ($this->getCollection() !== null) {
            return $state;
        }

        $disk = $this->evaluate($this->disk);
        $directory = $this->evaluate($this->directory);

        if ($this->is_multiple) {
            return array_map(fn (UploadedFile $file) => $file->store($directory, ['disk' => $disk]), (array) $state);
        }

        return $state instanceof UploadedFile
            ? $state->store($directory, ['disk' => $disk])
            : $state;
    }

    public function saveRelationship(Model $record, mixed $state): void
    {
        $collection = $this->getCollection();

        if ($collection === null) {
            return;
        }

        foreach (Arr::wrap($state) as $file) {
            if ($file instanceof UploadedFile) {
                $record->addMedia($file)->toMediaCollection($collection);
            }
        }
    }

    public function getValidationRules(): array
    {
        $rules = parent::getValidationRules();

        // A required file is satisfied by the one the record already has.
        if ($this->isRequired() && filled($this->hydrateState($this->getForm()->getRecord()))) {
            $rules = array_map(fn ($rule) => $rule === 'required' ? 'nullable' : $rule, $rules);
        }

        if ($this->is_multiple) {
            return $rules;
        }

        return [...$rules, ...$this->getFileRules()];
    }

    public function getAllValidationRules(): array
    {
        $rules = parent::getAllValidationRules();

        if ($this->is_multiple) {
            $rules["{$this->getStatePath()}.*"] = $this->getFileRules();
        }

        return $rules;
    }

    protected function getTypeRules(): array
    {
        return $this->is_multiple ? ['array'] : [];
    }

    /**
     * @return array<int, string>
     */
    protected function getFileRules(): array
    {
        $mimetypes = $this->evaluate($this->mimetypes);
        $max_size = $this->evaluate($this->max_size);

        return array_values(array_filter([
            'file',
            $mimetypes ? 'mimetypes:' . implode(',', (array) $mimetypes) : null,
            $max_size ? "max:{$max_size}" : null,
        ]));
    }

    protected function getFieldProps(): array
    {
        $state = $this->getState();

        return [
            'type' => $this->evaluate($this->file_type),
            'mimetypes' => $this->evaluate($this->mimetypes),
            'maxSize' => $this->evaluate($this->max_size),
            'multiple' => $this->is_multiple ?: null,
            'default' => is_string($state) || (is_array($state) && is_string(Arr::first($state))) ? $state : null,
        ];
    }
}

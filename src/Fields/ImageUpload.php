<?php

namespace Javaabu\Stencil\Fields;

use Closure;

/**
 * An image input with a preview.
 */
class ImageUpload extends FileUpload
{
    protected string $type = 'image';

    protected ?string $component = 'image';

    protected string|array|Closure $file_type = 'image';

    protected int|Closure|null $width = null;

    protected int|Closure|null $height = null;

    protected bool|Closure $is_circle = false;

    /**
     * The preview's size, in pixels.
     */
    public function previewSize(int|Closure|null $width, int|Closure|null $height = null): static
    {
        $this->width = $width;
        $this->height = $height ?? $width;

        return $this;
    }

    public function circle(bool|Closure $condition = true): static
    {
        $this->is_circle = $condition;

        return $this;
    }

    protected function getFieldProps(): array
    {
        return [
            ...parent::getFieldProps(),
            'multiple' => null,
            'width' => $this->evaluate($this->width),
            'height' => $this->evaluate($this->height),
            'circle' => $this->evaluate($this->is_circle) ?: null,
        ];
    }
}

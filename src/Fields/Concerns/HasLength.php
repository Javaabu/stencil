<?php

namespace Javaabu\Stencil\Fields\Concerns;

use Closure;

trait HasLength
{
    protected int|Closure|null $min_length = null;

    protected int|Closure|null $max_length = null;

    public function minLength(int|Closure|null $length): static
    {
        $this->min_length = $length;

        return $this;
    }

    public function maxLength(int|Closure|null $length): static
    {
        $this->max_length = $length;

        return $this;
    }

    public function getMinLength(): ?int
    {
        return $this->evaluate($this->min_length);
    }

    public function getMaxLength(): ?int
    {
        return $this->evaluate($this->max_length);
    }

    /**
     * @return array<int, string>
     */
    protected function getLengthRules(): array
    {
        return array_values(array_filter([
            $this->getMinLength() !== null ? "min:{$this->getMinLength()}" : null,
            $this->getMaxLength() !== null ? "max:{$this->getMaxLength()}" : null,
        ]));
    }

    /**
     * @return array<string, int|null>
     */
    protected function getLengthProps(): array
    {
        return [
            'minLength' => $this->getMinLength(),
            'maxLength' => $this->getMaxLength(),
        ];
    }
}

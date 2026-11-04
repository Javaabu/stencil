<?php

namespace Javaabu\Stencil\Fields;

/**
 * A single checkbox for a boolean. Unchecked is saved as false.
 */
class Checkbox extends Field
{
    protected string $type = 'checkbox';

    protected ?string $component = 'checkbox';

    public function getValidationRules(): array
    {
        $rules = parent::getValidationRules();

        if (! $this->isRequired()) {
            return [...$rules, 'boolean'];
        }

        return ['accepted', ...array_filter($rules, fn ($rule) => $rule !== 'required')];
    }

    public function normalizeState(mixed $state): mixed
    {
        return $state === null ? null : filter_var($state, FILTER_VALIDATE_BOOL);
    }

    protected function castDehydratedState(mixed $state): mixed
    {
        return filter_var($state, FILTER_VALIDATE_BOOL);
    }
}

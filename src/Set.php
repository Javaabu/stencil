<?php

namespace Javaabu\Stencil;

/**
 * Changes the form's state from closures: fn (Set $set, ?string $state) => $set('slug', Str::slug($state)).
 */
class Set
{
    public function __construct(
        protected Form $form,
    ) {}

    public function __invoke(string $path, mixed $value): void
    {
        $this->form->setStateValue($path, $value);
    }
}

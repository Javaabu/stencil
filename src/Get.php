<?php

namespace Javaabu\Stencil;

/**
 * Reads the form's current state in closures: fn (Get $get) => $get('type') === 'company'.
 */
class Get
{
    public function __construct(
        protected Form $form,
    ) {}

    /**
     * The value at a field name, in dot or bracket notation. Without a path, the whole state.
     */
    public function __invoke(?string $path = null, mixed $default = null): mixed
    {
        if ($path === null) {
            return $this->form->getState();
        }

        return $this->form->getStateValue($path, $default);
    }
}

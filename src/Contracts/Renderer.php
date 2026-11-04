<?php

namespace Javaabu\Stencil\Contracts;

use Javaabu\Stencil\Form;

/**
 * A driver: turns a form into output. Resolve the tree with $form->toNode();
 * closures are already evaluated, and hidden components are marked.
 */
interface Renderer
{
    public function render(Form $form): mixed;
}

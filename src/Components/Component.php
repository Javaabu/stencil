<?php

namespace Javaabu\Stencil\Components;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use Javaabu\Stencil\Components\Concerns\CanBeHidden;
use Javaabu\Stencil\Components\Concerns\HasChildren;
use Javaabu\Stencil\Components\Concerns\HasColumnSpan;
use Javaabu\Stencil\Components\Concerns\HasConditions;
use Javaabu\Stencil\Components\Concerns\HasExtraAttributes;
use Javaabu\Stencil\Concerns\EvaluatesClosures;
use Javaabu\Stencil\Exceptions\StencilException;
use Javaabu\Stencil\Form;
use Javaabu\Stencil\Get;
use Javaabu\Stencil\Rendering\Node;
use Javaabu\Stencil\Set;

/**
 * Anything in a schema: layout components, fields and display components.
 * Knows nothing about Blade or React; drivers render the Node it resolves to.
 */
abstract class Component
{
    use CanBeHidden;
    use Conditionable;
    use EvaluatesClosures;
    use HasChildren;
    use HasColumnSpan;
    use HasConditions;
    use HasExtraAttributes;
    use Macroable;
    use Tappable;

    /**
     * The type drivers render by: a Blade view (stencil::components.{type}) and a React component.
     */
    protected string $type;

    protected ?string $key = null;

    protected ?string $generated_key = null;

    protected ?Component $parent = null;

    protected ?string $view = null;

    /** @var array<string, mixed> */
    protected array $meta = [];

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * A key that identifies this component in the rendered output. Fields use their name.
     */
    public function key(?string $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function getKey(): string
    {
        return $this->key ?? $this->generated_key ?? $this->type;
    }

    /**
     * @internal set by the parent when the tree is built
     */
    public function generatedKey(string $key): static
    {
        $this->generated_key = $key;

        return $this;
    }

    /**
     * @internal set by the parent when the tree is built
     */
    public function parent(?Component $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function getParent(): ?Component
    {
        return $this->parent;
    }

    public function getForm(): Form
    {
        if ($this instanceof Form) {
            return $this;
        }

        if (! $this->parent) {
            throw StencilException::notInForm($this);
        }

        return $this->parent->getForm();
    }

    public function hasForm(): bool
    {
        if ($this instanceof Form) {
            return true;
        }

        return $this->parent?->hasForm() ?? false;
    }

    /**
     * Render this component with your own Blade view instead of stencil::components.{type}.
     */
    public function view(?string $view): static
    {
        $this->view = $view;

        return $this;
    }

    public function getView(): ?string
    {
        return $this->view;
    }

    /**
     * Extra data drivers receive in the node, untouched by Stencil.
     *
     * @param  array<string, mixed>  $meta
     */
    public function meta(array $meta): static
    {
        $this->meta = array_merge($this->meta, $meta);

        return $this;
    }

    /**
     * The component's own props for drivers, with closures evaluated.
     *
     * @return array<string, mixed>
     */
    public function getProps(): array
    {
        return [];
    }

    /**
     * Resolves this component, and the components inside it, for a driver.
     */
    public function toNode(): Node
    {
        if ($this->isHidden()) {
            return new Node(
                type: $this->getType(),
                key: $this->getKey(),
                hidden: true,
                span: $this->getColumnSpan(),
                source: $this,
            );
        }

        return new Node(
            type: $this->getType(),
            key: $this->getKey(),
            props: array_merge($this->getProps(), $this->getExtraAttributes()),
            children: array_map(fn (Component $child) => $child->toNode(), $this->getChildComponents()),
            conditions: $this->getConditionsForDriver(),
            span: $this->getColumnSpan(),
            view: $this->getView(),
            meta: $this->meta,
            source: $this,
        );
    }

    /**
     * @return array<string, Closure>
     */
    protected function closureArgumentsByName(): array
    {
        return [
            'component' => fn () => $this,
            'form' => fn () => $this->getForm(),
            'get' => fn () => new Get($this->getForm()),
            'set' => fn () => new Set($this->getForm()),
            'record' => fn () => $this->getForm()->getRecord(),
            'model' => fn () => $this->getForm()->getModelClass(),
            'operation' => fn () => $this->getForm()->getOperation(),
            'state' => fn () => $this->getForm()->getState(),
        ];
    }

    /**
     * @return array<class-string, Closure>
     */
    protected function closureArgumentsByType(): array
    {
        return [
            Get::class => fn () => new Get($this->getForm()),
            Set::class => fn () => new Set($this->getForm()),
            Form::class => fn () => $this->getForm(),
            Component::class => fn () => $this,
            Model::class => fn () => $this->getForm()->getRecord(),
        ];
    }
}

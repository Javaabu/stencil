<?php

namespace Javaabu\Stencil\Renderers;

use Illuminate\Support\Str;
use Javaabu\Stencil\Contracts\Renderer;
use Javaabu\Stencil\Form;
use Javaabu\Stencil\Rendering\Node;
use Javaabu\Stencil\Support\Props;

/**
 * Serializes the schema for JavaScript drivers (the React renderer, Inertia
 * props). The format is documented in docs/advanced-usage/json-format.md and
 * typed in resources/js/types.ts.
 */
class JsonRenderer implements Renderer
{
    /**
     * The format version. It changes only with a breaking change to the format.
     */
    public const int VERSION = 1;

    /**
     * @return array<string, mixed>
     */
    public function render(Form $form): array
    {
        $root = $form->toNode();

        return [
            'stencil' => self::VERSION,
            ...Props::toJson($root->props),
            'state' => Props::jsonValue($form->getState()),
            'live' => $form->getLiveConfig(),
            'components' => array_map($this->node(...), $root->children),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function node(Node $node): array
    {
        if ($node->hidden) {
            return array_filter([
                'type' => $node->type,
                'key' => $node->key,
                'name' => $node->name,
                'hidden' => true,
                'span' => $node->span,
            ], fn ($value) => $value !== null);
        }

        return array_filter([
            'type' => $node->type,
            'key' => $node->key,
            'name' => $node->name,
            'component' => $node->component === null ? null : Str::studly($node->component),
            'props' => Props::toJson($node->props) ?: null,
            'children' => $node->children ? array_map($this->node(...), $node->children) : null,
            'conditions' => $node->conditions ?: null,
            'live' => $node->live,
            'span' => $node->span,
            'meta' => $node->meta ? Props::jsonValue($node->meta) : null,
        ], fn ($value) => $value !== null);
    }
}

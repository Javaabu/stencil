<?php

namespace Javaabu\Stencil\Renderers;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Javaabu\Manifest\Facades\Manifest;
use Javaabu\Stencil\Contracts\Renderer;
use Javaabu\Stencil\Exceptions\StencilException;
use Javaabu\Stencil\Form;
use Javaabu\Stencil\Rendering\Node;
use Javaabu\Stencil\StencilManager;
use Javaabu\Stencil\Support\Props;

/**
 * Renders a form as HTML with javaabu/forms v2 Blade components. Each component
 * type has a view, stencil::components.{type}, publishable and replaceable.
 */
class BladeRenderer implements Renderer
{
    /**
     * Props passed to javaabu/forms components as named slots.
     *
     * @var array<int, string>
     */
    public const array SLOTS = ['help', 'prepend', 'append'];

    /**
     * Asset ids javaabu/forms needs for each of its components, required up front
     * for live forms, whose fields may appear after the page has loaded.
     *
     * @var array<string, array<int, string>>
     */
    public const array FORMS_ASSETS = [
        'select2' => ['forms::select'],
        'date' => ['forms::dates'],
        'datetime' => ['forms::dates'],
        'time' => ['forms::dates'],
        'file' => ['forms::files'],
        'image' => ['forms::files'],
        'file-upload' => ['forms::files'],
        'image-upload' => ['forms::files'],
        'wysiwyg' => ['forms::wysiwyg'],
    ];

    protected bool $live = false;

    /** @var array<string, mixed> */
    protected array $state = [];

    public function __construct(
        protected StencilManager $manager,
    ) {}

    public function render(Form $form): HtmlString
    {
        $root = $form->toNode();
        $live = $form->getLiveConfig();

        $this->live = $live !== null;
        $this->state = $form->getState();

        $this->guard($root);

        $this->requireAssets($root, $live !== null);

        return new HtmlString(view('stencil::form', [
            'form' => $form,
            'root' => $root,
            'state' => $form->getState(),
            'live' => $live,
            'stencil' => $this,
        ])->render());
    }

    /**
     * Renders the components of a container, in a grid when it has columns.
     *
     * @param  array<int, Node>  $nodes
     * @param  array<string, int>|null  $columns
     */
    public function children(array $nodes, ?array $columns = null): HtmlString
    {
        $html = implode('', array_map(fn (Node $node) => $this->node($node)->toHtml(), $nodes));

        if (! $columns) {
            return new HtmlString($html);
        }

        return new HtmlString('<div class="stencil-grid" style="' . e($this->columnsStyle($columns)) . '">' . $html . '</div>');
    }

    /**
     * Renders one component, wrapped for grid spans, conditions and live updates.
     */
    public function node(Node $node): HtmlString
    {
        if ($node->hidden) {
            return new HtmlString($this->live
                ? '<div data-stencil-key="' . e($node->key) . '" data-stencil-hash="hidden" hidden></div>'
                : '');
        }

        $view = $node->view ?? $this->viewFor($node);

        $html = view($view, [
            'node' => $node,
            'stencil' => $this,
        ])->render();

        foreach (array_reverse($node->conditions) as $condition) {
            $html = $this->component('conditional-wrapper', $this->conditionProps($condition), $html)->toHtml();
        }

        $attributes = [
            'class' => 'stencil-item',
            'data-stencil-key' => $node->key,
            'data-stencil-hash' => $this->hash($node),
            'data-stencil-live' => $node->live ? json_encode($node->live) : null,
            'style' => $node->span ? $this->spanStyle($node->span) : null,
        ];

        return new HtmlString('<div' . $this->attributes($attributes) . '>' . $html . '</div>');
    }

    /**
     * Renders a javaabu/forms component with props: component('text', ['name' => 'title', 'required' => true]).
     * Props in SLOTS become named slots; $slot is the default slot.
     *
     * @param  array<string, mixed>  $props
     */
    public function component(string $component, array $props, string|Htmlable|null $slot = null): HtmlString
    {
        $slots = array_filter(array_intersect_key($props, array_flip(self::SLOTS)), fn ($value) => $value !== null);
        $attributes = array_diff_key($props, array_flip(self::SLOTS));

        $template = "<x-forms::{$component}";

        foreach (array_keys($attributes) as $name) {
            $template .= ' :' . Props::toAttribute($name) . '="$__stencil_attributes[' . var_export($name, true) . ']"';
        }

        $template .= '>';

        foreach (array_keys($slots) as $name) {
            $template .= "<x-slot:{$name}>{{ \$__stencil_slots['{$name}'] }}</x-slot:{$name}>";
        }

        if ($slot !== null) {
            $template .= '{{ $__stencil_slot }}';
        }

        $template .= "</x-forms::{$component}>";

        return new HtmlString(Blade::render($template, [
            '__stencil_attributes' => $attributes,
            '__stencil_slots' => $slots,
            '__stencil_slot' => is_string($slot) ? new HtmlString($slot) : $slot,
        ]));
    }

    /**
     * Renders a field node as its javaabu/forms component.
     *
     * @param  array<string, mixed>  $props  props to add or replace
     */
    public function field(Node $node, array $props = []): HtmlString
    {
        if ($node->component === null && $node->translatable) {
            throw new StencilException("The [{$node->type}] field [{$node->key}] is translatable, which needs a javaabu/forms component.");
        }

        return $this->component($node->component, ['name' => $node->name, ...$node->props, ...$props]);
    }

    /**
     * The props of one translatable input: its name, label, direction, Thaana and
     * whether it's required (the primary locale, unless every locale is required).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function translatableProps(Node $node, array $input): array
    {
        return array_filter([
            ...$node->props,
            'name' => $input['name'],
            'label' => $input['label'],
            'required' => $node->prop('required') && $input['primary'] ? true : null,
            'thaana' => $input['thaana'] && in_array($node->component, ['text', 'textarea']) ? true : null,
            'dir' => $input['direction'],
            'lang' => $input['locale'],
        ], fn ($value) => $value !== null);
    }

    /**
     * A field's value for a custom view: old input, then the form's state.
     */
    public function value(Node $node): mixed
    {
        return old($node->key, data_get($this->state, $node->key));
    }

    public function viewFor(Node $node): string
    {
        $view = $this->manager->viewFor($node->translatable ? 'translatable' : $node->type);

        if (view()->exists($view)) {
            return $view;
        }

        return $node->isField() ? 'stencil::components.field' : 'stencil::components.group';
    }

    /**
     * javaabu/forms' conditional-wrapper attributes for a condition.
     *
     * @param  array<string, mixed>  $condition
     * @return array<string, mixed>
     */
    public function conditionProps(array $condition): array
    {
        $effect = $condition['effect'];

        return array_filter([
            'enableElem' => $condition['selector'],
            'enableValue' => $condition['checkbox'] ? 1 : (count($condition['values']) === 1 ? $condition['values'][0] : $condition['values']),
            'enableCheckbox' => $condition['checkbox'] ?: null,
            'hideFields' => in_array($effect, ['visible', 'hidden']) ?: null,
            'disable' => in_array($effect, ['hidden', 'disabled']) ?: null,
        ], fn ($value) => $value !== null);
    }

    /**
     * A digest of what the server decides about a component, so the live script
     * replaces only the components that changed.
     */
    public function hash(Node $node): string
    {
        if ($node->hidden) {
            return 'hidden';
        }

        $children = array_map(fn (Node $child) => $child->key, $node->children);

        return substr(md5(serialize([Props::toJson($node->props), $node->conditions, $node->live, $node->span, $children])), 0, 12);
    }

    /**
     * @param  array<string, int>  $columns
     */
    public function columnsStyle(array $columns): string
    {
        return collect($columns)
            ->map(fn (int $count, string $breakpoint) => '--stencil-cols' . ($breakpoint === 'default' ? '' : "-{$breakpoint}") . ": {$count}")
            ->implode('; ');
    }

    /**
     * @param  array<string, int|string>  $span
     */
    public function spanStyle(array $span): string
    {
        return collect($span)
            ->map(function (int|string $value, string $breakpoint) {
                $value = $value === 'full' ? '1 / -1' : "span {$value} / span {$value}";

                return '--stencil-span' . ($breakpoint === 'default' ? '' : "-{$breakpoint}") . ": {$value}";
            })
            ->implode('; ');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function attributes(array $attributes): string
    {
        $html = '';

        foreach ($attributes as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            $html .= $value === true ? " {$name}" : ' ' . $name . '="' . e($value) . '"';
        }

        return $html;
    }

    /**
     * A stable id for an option of a group: channels[] + sms → channels-sms.
     */
    public function optionId(Node $node, int|string $value): string
    {
        return Str::of($node->name)->before('[]')->replace(['[', ']'], ['-', ''])->append('-' . Str::slug((string) $value) ?: (string) $value)->toString();
    }

    /**
     * Fails early, outside the views, for fields there's nothing to render with.
     */
    protected function guard(Node $root): void
    {
        foreach ($root->descendants() as $node) {
            if ($node->hidden || ! $node->isField() || $node->component !== null || $node->translatable) {
                continue;
            }

            if ($node->view !== null || $this->viewFor($node) !== 'stencil::components.field') {
                continue;
            }

            throw new StencilException(
                "The [{$node->type}] field [{$node->key}] has no javaabu/forms component and no Blade view. "
                . "Set \$view on the field class, or register one with Stencil::view('{$node->type}', '…').",
            );
        }
    }

    protected function requireAssets(Node $root, bool $live): void
    {
        if (! config('stencil.assets', true)) {
            return;
        }

        $assets = ['stencil::styles'];

        if ($live) {
            foreach ($root->descendants() as $node) {
                array_push($assets, ...(self::FORMS_ASSETS[$node->component ?? ''] ?? []));
            }
        }

        if ($live || $this->hasClientBehaviour($root)) {
            $assets[] = 'stencil::stencil';
        }

        Manifest::require(array_values(array_unique($assets)));
    }

    protected function hasClientBehaviour(Node $root): bool
    {
        foreach ($root->descendants() as $node) {
            if ($node->type === 'wizard' && ! $node->hidden) {
                return true;
            }
        }

        return false;
    }
}

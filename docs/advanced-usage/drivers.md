---
title: Drivers
sidebar_position: 3
---

A driver turns a form into output. Stencil has two:

| Driver | Output |
| --- | --- |
| `blade` | An `HtmlString` built with javaabu/forms Blade components |
| `json` (also `inertia`) | An array in the [JSON format](./json-format.md), for Inertia and JavaScript renderers |

Render with one: `$form->render('blade')`, `Stencil::render($form, 'json')`, or the default from `config('stencil.views')`.

# How a driver works

The schema knows nothing about the drivers. A driver calls `$form->toNode()`, which evaluates every closure against the form's state and returns a tree of `Javaabu\Stencil\Rendering\Node`s:

| Property | What |
| --- | --- |
| `type` | What to render: `section`, `text`, `checkbox-list`, your custom types |
| `key` | Unique in the form; the field's name (dot notation) for fields |
| `name` | Fields: the input name in bracket notation (`address[city]`, `tags[]`) |
| `component` | Fields: the javaabu/forms component (`text`, `select2`, `file`) |
| `props` | Evaluated props, camelCase: for fields, javaabu/forms' props |
| `children` | Child nodes |
| `hidden` | Hidden on the server: render nothing |
| `conditions` | Conditions to apply in the browser |
| `live` | `{debounce, onBlur}` for live fields |
| `span` | Grid columns taken, per breakpoint |
| `translatable` | One input per locale, for translatable fields |
| `view`, `meta`, `source` | A Blade view override, extra data, and the schema component |

`Javaabu\Stencil\Support\Props` converts prop names between React (`showLabel`, `className`, `maxLength`) and Blade attributes (`show-label`, `class`, `maxlength`), and values to JSON.

# Writing a driver

Implement `Javaabu\Stencil\Contracts\Renderer` and register it:

```php
use Javaabu\Stencil\Contracts\Renderer;
use Javaabu\Stencil\Form;
use Javaabu\Stencil\Rendering\Node;

class VueRenderer implements Renderer
{
    public function render(Form $form): array
    {
        $root = $form->toNode();

        return [
            'state' => $form->getState(),
            'live' => $form->getLiveConfig(),
            'components' => array_map($this->node(...), $root->visibleChildren()),
        ];
    }

    protected function node(Node $node): array
    {
        return [
            'is' => $node->component ?? "stencil-{$node->type}",
            'name' => $node->name,
            'props' => $node->props,
            'children' => array_map($this->node(...), $node->visibleChildren()),
        ];
    }
}
```

```php
// a service provider's boot()
Stencil::extend('vue', fn ($app) => new VueRenderer);
```

```php
$form->render('vue');
// or config(['stencil.views' => 'vue']) and Stencil::render($form)
```

Most JavaScript drivers don't need PHP at all: render the `json` driver's output in your framework, as the React renderer does.

# Testing a driver

`Javaabu\Stencil\Testing\RendererTestKit` renders `KitchenSinkForm`, a form with every built-in type, and checks every visible field is in the output and hidden ones aren't:

```php
use Javaabu\Stencil\Testing\RendererTestKit;

class VueRendererTest extends TestCase
{
    use RendererTestKit;

    protected function renderer(): Renderer
    {
        return new VueRenderer;
    }
}
```

By default a field is "in the output" when HTML contains `name="…"`, or an array contains its key. Override `assertNodeRendered()` and `assertNodeNotRendered()` for other output, and `rendererTestForm()` to test another form.

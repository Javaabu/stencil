<?php

namespace Javaabu\Stencil\Testing;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Route;
use Javaabu\Stencil\Contracts\Renderer;
use Javaabu\Stencil\Form;
use Javaabu\Stencil\Rendering\Node;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Test;

/**
 * Checks a driver against every built-in component. Use it in a Testbench test
 * case for your driver and return the driver from renderer():
 *
 *     class VueRendererTest extends TestCase
 *     {
 *         use RendererTestKit;
 *
 *         protected function renderer(): Renderer { return new VueRenderer; }
 *     }
 *
 * Override assertNodeRendered() when your output doesn't contain field names.
 */
trait RendererTestKit
{
    abstract protected function renderer(): Renderer;

    /**
     * The form the kit renders. Defaults to KitchenSinkForm, with every built-in type.
     */
    protected function rendererTestForm(): Form
    {
        if (! Route::has(config('stencil.live.route'))) {
            Route::stencil();
        }

        return KitchenSinkForm::make()
            ->model(['title' => 'ރާއްޖެ', 'type' => 'person', 'channels' => ['email'], 'size' => 'l'])
            ->action('/kitchen-sink');
    }

    #[Test]
    public function it_renders_every_built_in_component(): void
    {
        $form = $this->rendererTestForm();
        $output = $this->renderer()->render($form);

        Assert::assertNotNull($output, 'The driver rendered nothing.');

        foreach ($form->toNode()->descendants() as $node) {
            if ($node->hidden || ! $node->isField()) {
                continue;
            }

            $this->assertNodeRendered($output, $node);
        }
    }

    #[Test]
    public function it_leaves_out_hidden_components(): void
    {
        $form = $this->rendererTestForm();
        $output = $this->renderer()->render($form);

        foreach ($form->toNode()->descendants() as $node) {
            if ($node->hidden && $node->isField()) {
                $this->assertNodeNotRendered($output, $node);
            }
        }
    }

    /**
     * Whether a field appears in the output. By default, its input name in the
     * HTML (name="…") or its key in the JSON.
     */
    protected function assertNodeRendered(mixed $output, Node $node): void
    {
        Assert::assertStringContainsString($this->needle($output, $node), $this->stringify($output), "The field [{$node->key}] isn't rendered.");
    }

    protected function assertNodeNotRendered(mixed $output, Node $node): void
    {
        if (! $output instanceof Htmlable && ! is_string($output)) {
            $found = $this->findSerializedNode($output instanceof Arrayable ? $output->toArray() : (array) $output, $node->key);

            Assert::assertTrue($found === null || ! isset($found['props']), "The hidden field [{$node->key}] is serialized with its props.");

            return;
        }

        Assert::assertStringNotContainsString($this->needle($output, $node), $this->stringify($output), "The hidden field [{$node->key}] is rendered.");
    }

    /**
     * @param  array<mixed>  $output
     * @return array<string, mixed>|null
     */
    protected function findSerializedNode(array $output, string $key): ?array
    {
        if (($output['key'] ?? null) === $key) {
            return $output;
        }

        foreach ($output as $value) {
            if (! is_array($value)) {
                continue;
            }

            $found = $this->findSerializedNode($value, $key);

            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    protected function needle(mixed $output, Node $node): string
    {
        if ($output instanceof Htmlable || is_string($output)) {
            return 'name="' . e($node->name) . '"';
        }

        return json_encode($node->key);
    }

    protected function stringify(mixed $output): string
    {
        return match (true) {
            $output instanceof Htmlable => $output->toHtml(),
            $output instanceof Arrayable => json_encode($output->toArray(), JSON_UNESCAPED_UNICODE),
            is_string($output) => $output,
            default => json_encode($output, JSON_UNESCAPED_UNICODE),
        };
    }
}

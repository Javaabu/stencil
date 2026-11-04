<?php

namespace Javaabu\Stencil\Components;

use Closure;
use Illuminate\Contracts\Support\Htmlable;

/**
 * A message box in the form: <x-forms::alert> / <Alert>.
 */
class Alert extends Component
{
    protected string $type = 'alert';

    protected string|Htmlable|Closure $message = '';

    protected string|Closure $alert_type = 'info';

    protected string|Htmlable|Closure|null $heading = null;

    protected string|Closure|null $icon = null;

    final public function __construct() {}

    public static function make(string|Htmlable|Closure $message): static
    {
        return app(static::class)->message($message);
    }

    public function message(string|Htmlable|Closure $message): static
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Bootstrap's alert colours: primary, success, info, warning, danger, ….
     */
    public function color(string|Closure $type): static
    {
        $this->alert_type = $type;

        return $this;
    }

    public function heading(string|Htmlable|Closure|null $heading): static
    {
        $this->heading = $heading;

        return $this;
    }

    public function icon(string|Closure|null $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getProps(): array
    {
        return array_filter([
            'message' => $this->evaluate($this->message),
            'type' => $this->evaluate($this->alert_type),
            'heading' => $this->evaluate($this->heading),
            'icon' => $this->evaluate($this->icon),
        ], fn ($value) => $value !== null);
    }
}

<?php

namespace Javaabu\Stencil\Support;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use JsonSerializable;
use Stringable;
use UnitEnum;

/**
 * Prop names and values for each driver: camelCase for React, kebab-case
 * attributes for Blade, and plain JSON values.
 */
class Props
{
    /**
     * React DOM props whose HTML attribute isn't their kebab case.
     *
     * @var array<string, string>
     */
    public const array HTML_ATTRIBUTES = [
        'className' => 'class',
        'htmlFor' => 'for',
        'maxLength' => 'maxlength',
        'minLength' => 'minlength',
        'autoComplete' => 'autocomplete',
        'autoFocus' => 'autofocus',
        'readOnly' => 'readonly',
        'tabIndex' => 'tabindex',
        'inputMode' => 'inputmode',
        'spellCheck' => 'spellcheck',
        'autoCapitalize' => 'autocapitalize',
        'enterKeyHint' => 'enterkeyhint',
    ];

    /**
     * The Blade attribute for a prop: showLabel → show-label, maxLength → maxlength.
     */
    public static function toAttribute(string $name): string
    {
        if (isset(self::HTML_ATTRIBUTES[$name])) {
            return self::HTML_ATTRIBUTES[$name];
        }

        if (str_contains($name, '-') || str_contains($name, ':')) {
            return $name;
        }

        return Str::kebab($name);
    }

    /**
     * The React prop for an attribute or prop: show-label → showLabel, class → className, data-id stays.
     */
    public static function toProp(string $name): string
    {
        $html = array_search($name, self::HTML_ATTRIBUTES, true);

        if ($html !== false) {
            return $html;
        }

        if (str_starts_with($name, 'data-') || str_starts_with($name, 'aria-')) {
            return $name;
        }

        return Str::camel($name);
    }

    /**
     * Props with React names and JSON values.
     *
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    public static function toJson(array $props): array
    {
        $json = [];

        foreach ($props as $name => $value) {
            $name = static::toProp($name);

            $json[$name] = $name === 'options' && is_array($value)
                ? static::optionsToJson($value)
                : static::jsonValue($value);
        }

        return $json;
    }

    /**
     * [value => label] as a list, so JavaScript keeps the order: [{value, label}].
     *
     * @param  array<int|string, mixed>  $options
     * @return array<int, array{value: string, label: mixed}>
     */
    public static function optionsToJson(array $options): array
    {
        $list = [];

        foreach ($options as $value => $label) {
            $list[] = ['value' => (string) $value, 'label' => static::jsonValue($label)];
        }

        return $list;
    }

    public static function jsonValue(mixed $value): mixed
    {
        return match (true) {
            $value instanceof Htmlable => $value->toHtml(),
            $value instanceof BackedEnum => $value->value,
            $value instanceof UnitEnum => $value->name,
            $value instanceof DateTimeInterface => $value->format(DateTimeInterface::ATOM),
            $value instanceof UploadedFile => null,
            $value instanceof JsonSerializable => $value->jsonSerialize(),
            $value instanceof Arrayable => static::jsonValue($value->toArray()),
            $value instanceof Stringable => (string) $value,
            is_array($value) => array_map(static::jsonValue(...), $value),
            default => $value,
        };
    }
}

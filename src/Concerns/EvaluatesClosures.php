<?php

namespace Javaabu\Stencil\Concerns;

use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Arr;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Evaluates props given as closures, injecting arguments by name ($get, $set,
 * $state, $record, $operation, …) or by type (Get, Set, the record's class,
 * anything the container can build).
 */
trait EvaluatesClosures
{
    /**
     * @param  array<string, mixed>  $named  arguments by parameter name
     * @param  array<class-string, mixed>  $typed  arguments by parameter type
     */
    public function evaluate(mixed $value, array $named = [], array $typed = []): mixed
    {
        if (! $value instanceof Closure) {
            return $value;
        }

        $arguments = array_map(
            fn (ReflectionParameter $parameter) => $this->resolveClosureArgument($parameter, $named, $typed),
            (new ReflectionFunction($value))->getParameters(),
        );

        return $value(...$arguments);
    }

    /**
     * Arguments every closure on this object can ask for by name. Values may be
     * closures, which are called only when a closure asks for that argument.
     *
     * @return array<string, Closure>
     */
    protected function closureArgumentsByName(): array
    {
        return [];
    }

    /**
     * Arguments every closure on this object can ask for by type.
     *
     * @return array<class-string, Closure>
     */
    protected function closureArgumentsByType(): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $named
     * @param  array<class-string, mixed>  $typed
     */
    protected function resolveClosureArgument(ReflectionParameter $parameter, array $named, array $typed): mixed
    {
        $name = $parameter->getName();

        if (array_key_exists($name, $named)) {
            return $named[$name];
        }

        $defaults = $this->closureArgumentsByName();

        if (array_key_exists($name, $defaults)) {
            return $defaults[$name]();
        }

        $type = $this->closureParameterType($parameter);

        if ($type !== null) {
            foreach ($typed as $class => $argument) {
                if (is_a($argument, $type) || is_a($class, $type, true)) {
                    return $argument;
                }
            }

            foreach ($this->closureArgumentsByType() as $class => $resolver) {
                if (! is_a($class, $type, true) && ! is_a($type, $class, true)) {
                    continue;
                }

                $argument = $resolver();

                if ($argument === null && $parameter->allowsNull()) {
                    return null;
                }

                if ($argument instanceof $type) {
                    return $argument;
                }
            }

            if (app()->bound($type)) {
                return app()->make($type);
            }

            if (! $parameter->isDefaultValueAvailable() && ! $parameter->allowsNull() && class_exists($type)) {
                return app()->make($type);
            }
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->hasType() && $parameter->allowsNull()) {
            return null;
        }

        throw new BindingResolutionException(sprintf(
            'Stencil could not resolve [$%s] for a closure on [%s]. Closures can ask for: %s.',
            $name,
            static::class,
            Arr::join(array_map(fn (string $key) => '$' . $key, array_keys($defaults)), ', ', ' and '),
        ));
    }

    protected function closureParameterType(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType) {
            return null;
        }

        if ($type->isBuiltin()) {
            return null;
        }

        return $type->getName();
    }
}

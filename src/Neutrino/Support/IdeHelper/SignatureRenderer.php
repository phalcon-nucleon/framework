<?php

declare(strict_types=1);

namespace Neutrino\Support\IdeHelper;

use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;
use Throwable;

/**
 * Renders method signatures for PHPDoc `@method` tags.
 *
 * @internal
 */
final class SignatureRenderer
{
    /**
     * `ReturnType name(Type $param = default, ...)`, with `self` and `static` resolved to `$class`.
     *
     * @param class-string $class
     */
    public static function method(ReflectionMethod $method, string $class): string
    {
        $parameters = array_map(
            static fn(ReflectionParameter $parameter): string => self::parameter($parameter, $class),
            $method->getParameters(),
        );

        return self::type($method->getReturnType(), $class) . ' ' . $method->getName() . '(' . implode(', ', $parameters) . ')';
    }

    /**
     * @param class-string $class
     */
    public static function type(?ReflectionType $type, string $class): string
    {
        if ($type === null) {
            return 'mixed';
        }

        if ($type instanceof ReflectionUnionType) {
            return implode('|', array_map(static fn(ReflectionType $t): string => self::type($t, $class), $type->getTypes()));
        }

        if ($type instanceof ReflectionIntersectionType) {
            return implode('&', array_map(static fn(ReflectionType $t): string => self::type($t, $class), $type->getTypes()));
        }

        if (!$type instanceof ReflectionNamedType) {
            return 'mixed';
        }

        $name = match ($type->getName()) {
            'self', 'static' => '\\' . $class,
            default => $type->isBuiltin() ? $type->getName() : '\\' . $type->getName(),
        };

        return $type->allowsNull() && !in_array($name, ['mixed', 'null'], true) ? $name . '|null' : $name;
    }

    /**
     * @param class-string $class
     */
    private static function parameter(ReflectionParameter $parameter, string $class): string
    {
        $rendered = $parameter->hasType() ? self::type($parameter->getType(), $class) . ' ' : '';
        $rendered .= $parameter->isPassedByReference() ? '&' : '';
        $rendered .= $parameter->isVariadic() ? '...' : '';
        $rendered .= '$' . $parameter->getName();

        if (!$parameter->isVariadic() && $parameter->isOptional()) {
            $default = self::defaultValue($parameter, $class);
            if ($default !== null) {
                $rendered .= ' = ' . $default;
            }
        }

        return $rendered;
    }

    /**
     * @param class-string $class
     */
    private static function defaultValue(ReflectionParameter $parameter, string $class): ?string
    {
        try {
            if (!$parameter->isDefaultValueAvailable()) {
                // Optional parameter of an extension method whose default is not exposed.
                return $parameter->allowsNull() ? 'null' : null;
            }

            if ($parameter->isDefaultValueConstant()) {
                $constant = (string) $parameter->getDefaultValueConstantName();

                return str_starts_with($constant, 'self::') ? '\\' . $class . substr($constant, 4) : '\\' . ltrim($constant, '\\');
            }

            return self::export($parameter->getDefaultValue());
        } catch (Throwable) {
            return null;
        }
    }

    private static function export(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            $value === [] => '[]',
            is_array($value) => '[' . implode(', ', array_map(
                static fn(mixed $k, mixed $v): string => (array_is_list($value) ? '' : self::export($k) . ' => ') . self::export($v),
                array_keys($value),
                $value,
            )) . ']',
            is_scalar($value) => var_export($value, true),
            default => 'null',
        };
    }
}

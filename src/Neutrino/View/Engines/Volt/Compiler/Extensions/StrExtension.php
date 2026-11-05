<?php

declare(strict_types=1);

namespace Neutrino\View\Engines\Volt\Compiler\Extensions;

use Neutrino\Support\Str;
use Neutrino\View\Engines\Volt\Compiler\ExtensionExtend;
use ReflectionMethod;

/**
 * `{{ str_<method>(...) }}` calls the public static methods of {@see Str} (`str_slug(title)`), unless a PHP
 * function has this name (`str_replace`). Filters `slug`, `limit` and `words`.
 */
class StrExtension extends ExtensionExtend
{
    private const array FILTERS = ['slug', 'limit', 'words'];

    public function compileFunction(string $name, string $arguments, ?array $funcArguments): ?string
    {
        if (!str_starts_with($name, 'str_') || function_exists($name)) {
            return null;
        }

        $method = substr($name, 4);

        if (!method_exists(Str::class, $method)) {
            return null;
        }

        $reflection = new ReflectionMethod(Str::class, $method);

        return $reflection->isPublic() && $reflection->isStatic() ? '\\' . Str::class . '::' . $method . '(' . $arguments . ')' : null;
    }

    public function compileFilter(string $name, string $arguments, ?array $funcArguments): ?string
    {
        return in_array($name, self::FILTERS, true) ? '\\' . Str::class . '::' . $name . '(' . $arguments . ')' : null;
    }
}

<?php

declare(strict_types=1);

namespace Neutrino\Cli\Output;

use Neutrino\Cli\Attribute\Argument;
use Neutrino\Cli\Attribute\Description;
use Neutrino\Cli\Attribute\Option;
use Neutrino\Version;
use Phalcon\Cli\Router\RouteInterface as CliRouteInterface;
use Phalcon\Mvc\Router\RouteInterface as MvcRouteInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/**
 * Console helpers: decoration-aware string width and padding, route patterns, task documentation.
 */
final class Helper
{
    /** @var array<class-string, ReflectionClass<object>> */
    private static array $reflections = [];

    /** @var array<string, true> Tasks already reported as documented by docblocks */
    private static array $deprecations = [];

    public static function removeDecoration(string $string): string
    {
        return (string) preg_replace("/\033\\[[^m]*m/", '', $string);
    }

    public static function strlenWithoutDecoration(string $string): int
    {
        return self::strlen(self::removeDecoration($string));
    }

    public static function strlen(string $string): int
    {
        $encoding = mb_detect_encoding($string, null, true);

        return $encoding === false ? strlen($string) : mb_strwidth($string, $encoding);
    }

    /**
     * str_pad() that ignores the ANSI decorations.
     */
    public static function strPad(string $str, int $size, string $pad, int $type = STR_PAD_RIGHT): string
    {
        $missing = max(0, $size - self::strlenWithoutDecoration($str));

        return match ($type) {
            STR_PAD_BOTH => str_repeat($pad, intdiv($missing, 2)) . $str . str_repeat($pad, $missing - intdiv($missing, 2)),
            STR_PAD_LEFT => str_repeat($pad, $missing) . $str,
            default      => $str . str_repeat($pad, $missing),
        };
    }

    /**
     * Readable form of a route pattern: `make:migration {name}`, `/users/{id}`.
     */
    public static function describeRoutePattern(CliRouteInterface|MvcRouteInterface $route, bool $decorate = false): string
    {
        $paths = $route->getPaths();
        $compiled = $route->getCompiledPattern();
        $pattern = $route->getPattern();

        if ($compiled === $pattern) {
            return $compiled;
        }

        preg_match_all('/(:[\w_]+|\([^?][^\/\)]+\))/', $pattern, $matches);

        foreach ($matches[1] as $match) {
            if (in_array($match, [':controller', ':module', ':action', ':namespace'], true)) {
                $name = '{' . substr($match, 1) . '}';
                $compiled = (string) preg_replace('/\([^?][^\/\)]+\)/', $decorate ? Decorate::notice($name) : $name, $compiled, 1);
            }
        }

        foreach ($paths as $key => $value) {
            if (is_int($value) && !in_array($key, ['controller', 'task', 'action', 'middleware'], true)) {
                $name = '{' . $key . '}';
                $compiled = (string) preg_replace('/\([^?][^\/\)]+\)/', $decorate ? Decorate::notice($name) : $name, $compiled, 1);
            }
        }

        return preg_match('/\^(.+)\$/', $compiled, $found) === 1 ? $found[1] : $compiled;
    }

    /**
     * Documentation of a task action: `#[Description]`, `#[Argument]` and `#[Option]` attributes.
     *
     * Docblocks (`@description`, `@argument`, `@option`, or the text of the docblock) are read when the action has
     * no attribute: deprecated, they disappear with `opcache.save_comments=0` and will no longer be read in 3.0.
     *
     * @return array{description: string, arguments?: list<string>, options?: list<string>}|array{__exception: string}
     */
    public static function getTaskInfos(string $class, string $methodName): array
    {
        try {
            $method = self::getReflection($class)->getMethod($methodName);
        } catch (ReflectionException) {
            return ['__exception' => "Methods $class::$methodName not found."];
        }

        $infos = self::fromAttributes($method);

        if ($infos !== null) {
            return $infos;
        }

        $infos = self::fromDocBlock($method);

        if ($infos['description'] !== '' && !isset(self::$deprecations[$class . '::' . $methodName])) {
            self::$deprecations[$class . '::' . $methodName] = true;
            @trigger_error(
                "Documenting the task $class::$methodName with a docblock is deprecated: use the attributes of Neutrino\\Cli\\Attribute.",
                E_USER_DEPRECATED,
            );
        }

        return $infos;
    }

    public static function neutrinoVersion(): string
    {
        return Decorate::info('Neutrino framework') . ' version ' . Decorate::notice('v' . Version::get() . ' [' . Version::getId() . ']');
    }

    /**
     * @return array{description: string, arguments?: list<string>, options?: list<string>}|null
     */
    private static function fromAttributes(ReflectionMethod $method): ?array
    {
        $description = $method->getAttributes(Description::class);
        $arguments = $method->getAttributes(Argument::class);
        $options = $method->getAttributes(Option::class);

        if ($description === [] && $arguments === [] && $options === []) {
            return null;
        }

        $infos = ['description' => $description === [] ? '' : $description[0]->newInstance()->text];

        foreach ($arguments as $argument) {
            $infos['arguments'][] = (string) $argument->newInstance();
        }
        foreach ($options as $option) {
            $infos['options'][] = (string) $option->newInstance();
        }

        return $infos;
    }

    /**
     * @return array{description: string, arguments?: list<string>, options?: list<string>}
     */
    private static function fromDocBlock(ReflectionMethod $method): array
    {
        $docBlock = (string) $method->getDocComment();
        $infos = ['description' => ''];

        preg_match_all('/\*\s*@(\w+)(.*)/', $docBlock, $annotations);
        $text = (string) preg_replace('/\*\s*@(\w+)(.*)/', '', $docBlock);

        foreach ($annotations[1] as $k => $annotation) {
            $value = trim($annotations[2][$k]);

            match ($annotation) {
                'description' => $infos['description'] = $value,
                'argument'    => $infos['arguments'][] = $value,
                'option'      => $infos['options'][] = $value,
                default       => null,
            };
        }

        if ($infos['description'] === '') {
            preg_match_all('/\*([^\n\r]+)/', $text, $lines);

            $rows = [];
            foreach ($lines[1] as $line) {
                if ($line !== '*' && $line !== '/') {
                    $rows[] = (string) preg_replace('/^ /', '', rtrim($line));
                }
            }

            $infos['description'] = trim(implode(PHP_EOL, $rows));
        }

        return $infos;
    }

    /**
     * @return ReflectionClass<object>
     */
    private static function getReflection(string $class): ReflectionClass
    {
        if (!class_exists($class)) {
            throw new ReflectionException("Class $class does not exist.");
        }

        return self::$reflections[$class] ??= new ReflectionClass($class);
    }
}

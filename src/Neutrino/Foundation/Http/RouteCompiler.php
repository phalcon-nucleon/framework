<?php

declare(strict_types=1);

namespace Neutrino\Foundation\Http;

use Neutrino\Foundation\Http\Exception\UncacheableRouteException;
use Neutrino\Support\AtomicFile;
use Phalcon\Mvc\Router;
use Phalcon\Mvc\Router\RouteInterface;
use ReflectionProperty;

/**
 * Compiles the HTTP routes into a PHP file of `$router->add()` calls (`route:cache`).
 *
 * Phalcon's own `Router::dumpDispatcher()` restores the routes with their indexes, but it is slower
 * to load than re-adding the routes (about 205 µs against 150 µs for 50 routes, first request included)
 * and it loses the router defaults, `removeExtraSlashes()` and `notFound()`.
 *
 * Routes that would be lost are rejected: a closure in the paths, converters, `beforeMatch()`, `match()`.
 */
final class RouteCompiler
{
    public const string COMPILED_FILE = '/bootstrap/compile/http-routes.php';

    /**
     * @throws UncacheableRouteException
     */
    public static function compile(Router $router): string
    {
        $code = "<?php\n\n/** @var \\Phalcon\\Mvc\\Router \$router */\n"
            . "\$router = \\Phalcon\\Di\\Di::getDefault()->getShared('router');\n\n";

        $code .= '$router->setDefaults(' . self::export($router->getDefaults(), 'defaults') . ");\n";

        if (self::property($router, 'removeExtraSlashes') === true) {
            $code .= "\$router->removeExtraSlashes(true);\n";
        }

        $notFound = self::property($router, 'notFoundPaths');
        if ($notFound !== null) {
            $code .= '$router->notFound(' . self::export($notFound, 'notFound') . ");\n";
        }

        $code .= "\n";

        /** @var RouteInterface $route */
        foreach ($router->getRoutes() as $route) {
            $code .= self::compileRoute($route);
        }

        return $code;
    }

    /**
     * Writes the compiled routes of `$router` in the application, atomically.
     *
     * @return string The compiled file
     *
     * @throws UncacheableRouteException
     */
    public static function write(Router $router, string $basePath): string
    {
        $file = $basePath . self::COMPILED_FILE;

        AtomicFile::write($file, self::compile($router));

        return $file;
    }

    public static function clear(string $basePath): void
    {
        $file = $basePath . self::COMPILED_FILE;

        if (is_file($file)) {
            unlink($file);
        }
    }

    private static function compileRoute(RouteInterface $route): string
    {
        $pattern = $route->getPattern();

        if ($route instanceof Router\Route) {
            if ($route->getConverters() !== []) {
                throw new UncacheableRouteException($pattern, 'it has converters');
            }
            // The stubs type getBeforeMatch() and getMatch() as null: they return the callback, or null.
            if ($route->getBeforeMatch() !== null) { // @phpstan-ignore notIdentical.alwaysFalse
                throw new UncacheableRouteException($pattern, 'it has a beforeMatch callback');
            }
            if ($route->getMatch() !== null) { // @phpstan-ignore notIdentical.alwaysFalse
                throw new UncacheableRouteException($pattern, 'it has a match callback');
            }
            if ($route->getGroup()?->getBeforeMatch() !== null) { // @phpstan-ignore notIdentical.alwaysFalse
                throw new UncacheableRouteException($pattern, 'its group has a beforeMatch callback');
            }
        }

        $code = '$router->add('
            . var_export($pattern, true) . ', '
            . self::export($route->getPaths(), $pattern) . ', '
            . self::export($route->getHttpMethods(), $pattern) . ')';

        $name = $route->getName();
        if ($name !== null && $name !== '') {
            $code .= '->setName(' . var_export($name, true) . ')';
        }

        $hostname = $route->getHostname() ?? ($route instanceof Router\Route ? $route->getGroup()?->getHostname() : null);
        if ($hostname !== null && $hostname !== '') {
            $code .= '->setHostname(' . var_export($hostname, true) . ')';
        }

        return $code . ";\n";
    }

    /**
     * `var_export()` of a value made of scalars and arrays only.
     *
     * @throws UncacheableRouteException
     */
    private static function export(mixed $value, string $pattern): string
    {
        self::assertExportable($value, $pattern, '');

        return var_export($value, true);
    }

    /**
     * @throws UncacheableRouteException
     */
    private static function assertExportable(mixed $value, string $pattern, string $path): void
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                self::assertExportable($item, $pattern, $path === '' ? (string) $key : "$path.$key");
            }
        } elseif ($value !== null && !is_scalar($value)) {
            throw new UncacheableRouteException($pattern, sprintf('"%s" is a %s', $path, get_debug_type($value)));
        }
    }

    private static function property(Router $router, string $name): mixed
    {
        return (new ReflectionProperty(Router::class, $name))->getValue($router);
    }
}

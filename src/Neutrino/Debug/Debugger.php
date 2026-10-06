<?php

declare(strict_types=1);

namespace Neutrino\Debug;

use Neutrino\Constants\Services;
use Neutrino\Error\Error;
use Neutrino\Error\Handler;
use Neutrino\Interfaces\Kernelable;
use Neutrino\Version;
use Phalcon\Config\ConfigInterface;
use Phalcon\DebugBar\Debug;
use Phalcon\DebugBar\DebugBar;
use Phalcon\DebugBar\Logger\Adapter as DebugBarLogger;
use Phalcon\DebugBar\Provider;
use Phalcon\Di\Di;
use Phalcon\Di\DiInterface;
use Phalcon\Events\EventInterface;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Events\ManagerInterface;
use Phalcon\Logger\Logger;
use Phalcon\Mvc\Application;
use Throwable;

/**
 * Debug mode, registered by `Foundation\Bootstrap` when `APP_DEBUG` is true (outside tests and the console):
 * - the fatal errors are shown on the debug error page ({@see self::renderErrorPage()}), with the PHP errors of the request;
 * - with `phalcon/debugbar` installed (a suggested dependency), the debug bar is added to the HTML pages of the HTTP kernel.
 *
 * The bar is configured by `debug.bar` (the options of `Phalcon\DebugBar\Provider`): `['enabled' => false]` disables it.
 */
final class Debugger
{
    /**
     * The name of the bar's adapter, added to the `logger` service.
     */
    public const string LOGGER_ADAPTER = 'debugbar';

    private static bool $enabled = false;

    private function __construct() {}

    public static function register(Kernelable $kernel): void
    {
        if (self::$enabled) {
            return;
        }

        self::$enabled = true;

        Handler::addWriter(DebugErrorLogger::class);

        if ($kernel instanceof Application && class_exists(Provider::class)) {
            self::registerDebugBar($kernel);
        }
    }

    public static function isEnabled(): bool
    {
        return self::$enabled;
    }

    /**
     * Leaves the debug mode (tests). The error writer and the debug bar stay registered on their kernel.
     *
     * @internal
     */
    public static function reset(): void
    {
        self::$enabled = false;
        DebugErrorLogger::reset();

        if (class_exists(Debug::class)) {
            Debug::setBar(null);
        }
    }

    /**
     * The debug error page: the error or the chain of exceptions with their code and trace, and the PHP errors
     * of the request. The code is highlighted with `tempest/highlight` when installed.
     */
    public static function renderErrorPage(Error $error): string
    {
        $exceptions = [];
        for ($exception = $error->exception; $exception !== null; $exception = $exception->getPrevious()) {
            $exceptions[] = $exception;
        }

        $phpErrors = array_values(array_filter(DebugErrorLogger::errors(), static fn(Error $logged): bool => $logged !== $error));

        return self::render(__DIR__ . '/resources/errors.html.php', [
            'error'      => $error,
            'exceptions' => $exceptions,
            'phpErrors'  => $phpErrors,
            'build'      => self::getBuildInfo(),
        ]);
    }

    /**
     * @return array{php: string, phalcon: string, nucleon: string}
     */
    public static function getBuildInfo(): array
    {
        return [
            'php'     => PHP_VERSION,
            'phalcon' => (new \Phalcon\Support\Version())->get(),
            'nucleon' => Version::get(),
        ];
    }

    /**
     * A path relative to the application (`BASE_PATH`).
     */
    public static function relativePath(string $file): string
    {
        $file = str_replace('\\', '/', $file);
        $base = defined('BASE_PATH') ? str_replace('\\', '/', BASE_PATH) . '/' : null;

        return $base !== null && str_starts_with($file, $base) ? substr($file, strlen($base)) : $file;
    }

    /**
     * Boots the debug bar on the application, and gives the application's events manager to the services
     * resolved afterwards (connections, cache stores, views…): the bar collects their events.
     */
    private static function registerDebugBar(Application $app): void
    {
        $di = $app->getDI();
        $config = $di->has(Services::CONFIG) ? $di->getShared(Services::CONFIG) : null;
        $options = $config instanceof ConfigInterface ? $config->path('debug.bar') : null;
        $options = $options instanceof ConfigInterface ? $options->toArray() : (is_array($options) ? $options : []);

        // The bar reads the environment in a variable (APP_ENV by default), Nucleon in a constant.
        $env = $options['env'] ?? null;
        $variable = is_array($env) && is_string($env['var'] ?? null) ? $env['var'] : 'APP_ENV';
        if (getenv($variable) === false && !isset($_ENV[$variable]) && !isset($_SERVER[$variable])) {
            $_ENV[$variable] = APP_ENV;
        }

        (new Provider($app, $options))->boot(); // @phpstan-ignore argument.type (options of the config, checked by the bar)

        $bar = Debug::getBar();
        $eventsManager = $app->getEventsManager();

        if ($bar === null || $eventsManager === null || !$di instanceof Di) {
            return;
        }

        $internal = $di->getInternalEventsManager();
        if ($internal === null) {
            $di->setInternalEventsManager($internal = $eventsManager);
        }

        $internal->attach('di:afterServiceResolve', static function (EventInterface $event, DiInterface $di, mixed $data) use ($eventsManager, $bar): void {
            self::instrument(is_array($data) ? $data['instance'] ?? null : null, $eventsManager, $bar);
        });
    }

    private static function instrument(mixed $service, ManagerInterface $eventsManager, DebugBar $bar): void
    {
        if ($service instanceof EventsAwareInterface && $service->getEventsManager() === null) {
            $service->setEventsManager($eventsManager);
        }

        if ($service instanceof Logger && !array_key_exists(self::LOGGER_ADAPTER, $service->getAdapters())) {
            $service->addAdapter(self::LOGGER_ADAPTER, new DebugBarLogger($bar));
        }
    }

    /**
     * @param array<string, mixed> $vars
     */
    private static function render(string $template, array $vars): string
    {
        ob_start();

        try {
            (static function (string $__template, array $__vars): void {
                extract($__vars);
                require $__template;
            })($template, $vars);
        } catch (Throwable $e) {
            ob_end_clean();

            throw $e;
        }

        return (string) ob_get_clean();
    }
}

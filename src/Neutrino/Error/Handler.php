<?php

declare(strict_types=1);

namespace Neutrino\Error;

use Neutrino\Error\Writer\Phplog;
use Neutrino\Error\Writer\Writable;
use Throwable;

/**
 * Error and uncaught exception handler: passes each error to the writers of the kernel (`$errorHandlerLvl`).
 *
 * `Foundation\Bootstrap::make()` registers it, unless `error.register` is `false`.
 */
final class Handler
{
    /**
     * The error types that stop the script, reported by the shutdown function.
     */
    public const int FATAL = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_RECOVERABLE_ERROR;

    /** @var array<class-string<Writable>, Writable|null> */
    private static array $writers = [Phplog::class => null];

    private static bool $registered = false;

    private static bool $shutdown = false;

    private function __construct() {}

    /**
     * @param class-string<Writable> $writer
     */
    public static function addWriter(string $writer): void
    {
        self::$writers[$writer] ??= null;
    }

    /**
     * @param list<class-string<Writable>> $writers
     */
    public static function setWriters(array $writers): void
    {
        self::$writers = array_fill_keys($writers, null);
    }

    /**
     * @return list<class-string<Writable>>
     */
    public static function getWriters(): array
    {
        return array_keys(self::$writers);
    }

    /**
     * Registers the error handler, the exception handler and the shutdown function (for the fatal errors).
     * Does nothing when already registered.
     */
    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;

        set_error_handler(self::handleError(...));
        set_exception_handler(self::handleException(...));

        if (!self::$shutdown) {
            self::$shutdown = true;
            register_shutdown_function(self::handleShutdown(...));
        }
    }

    /**
     * Restores the previous error and exception handlers. The shutdown function stays, inactive.
     */
    public static function unregister(): void
    {
        if (!self::$registered) {
            return;
        }

        self::$registered = false;

        restore_error_handler();
        restore_exception_handler();
    }

    public static function isRegistered(): bool
    {
        return self::$registered;
    }

    /**
     * A PHP error. The errors excluded by `error_reporting` (or silenced by `@`) are left to PHP.
     */
    public static function handleError(int $errno, string $errstr, string $errfile = '', int $errline = 0): bool
    {
        if (($errno & error_reporting()) === 0) {
            return false;
        }

        self::handle(Error::fromError($errno, $errstr, $errfile, $errline));

        return true;
    }

    public static function handleException(Throwable $exception): void
    {
        self::handle(Error::fromException($exception));
    }

    /**
     * Reports the fatal error that stopped the script, which the error handler cannot catch.
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();

        if (!self::$registered || $error === null || ($error['type'] & self::FATAL) === 0) {
            return;
        }

        self::handleError($error['type'], $error['message'], $error['file'], $error['line']);
    }

    /**
     * Passes an error to every writer. A failing writer does not prevent the others from running.
     */
    public static function handle(Error $error): void
    {
        foreach (self::$writers as $class => $writer) {
            try {
                $writer ??= self::$writers[$class] = new $class();
                $writer->handle($error);
            } catch (Throwable $e) {
                error_log('Error writer ' . $class . ' failed: ' . $e->getMessage() . ' in ' . $e->getFile() . '(' . $e->getLine() . ')');
            }
        }
    }
}

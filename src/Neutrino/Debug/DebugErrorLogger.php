<?php

declare(strict_types=1);

namespace Neutrino\Debug;

use Neutrino\Error\Error;
use Neutrino\Error\Writer\Writable;
use Phalcon\DebugBar\Debug;
use Phalcon\Logger\Enum;

/**
 * Error writer of the debug mode: keeps the errors of the request for the debug error page, and passes them
 * to the debug bar when installed.
 */
final class DebugErrorLogger implements Writable
{
    /** @var list<Error> */
    private static array $errors = [];

    public function handle(Error $error): void
    {
        self::$errors[] = $error;

        if (!class_exists(Debug::class)) {
            return;
        }

        if ($error->exception !== null) {
            Debug::addException($error->exception);

            return;
        }

        Debug::message(
            $error->typeStr . ': ' . $error->message . ' in ' . Debugger::relativePath($error->file) . '(' . $error->line . ')',
            match ($error->logLvl) {
                Enum::WARNING => 'warning',
                Enum::NOTICE  => 'notice',
                Enum::INFO, Enum::DEBUG => 'info',
                default       => 'error',
            },
        );
    }

    /**
     * @return list<Error>
     */
    public static function errors(): array
    {
        return self::$errors;
    }

    /**
     * @internal
     */
    public static function reset(): void
    {
        self::$errors = [];
    }
}

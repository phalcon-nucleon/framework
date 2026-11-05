<?php

declare(strict_types=1);

namespace Neutrino\Support\Facades;

use Neutrino\Constants\Services;

/**
 * The `logger` service ({@see \Phalcon\Logger\Logger}).
 *
 * @method static void emergency(string $message, array<string, mixed> $context = [])
 * @method static void alert(string $message, array<string, mixed> $context = [])
 * @method static void critical(string $message, array<string, mixed> $context = [])
 * @method static void error(string $message, array<string, mixed> $context = [])
 * @method static void warning(string $message, array<string, mixed> $context = [])
 * @method static void notice(string $message, array<string, mixed> $context = [])
 * @method static void info(string $message, array<string, mixed> $context = [])
 * @method static void debug(string $message, array<string, mixed> $context = [])
 * @method static void log(int|string $level, string $message, array<string, mixed> $context = [])
 */
class Log extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Services::LOGGER;
    }
}

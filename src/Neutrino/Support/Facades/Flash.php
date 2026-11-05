<?php

declare(strict_types=1);

namespace Neutrino\Support\Facades;

use Neutrino\Constants\Services;

/**
 * The `flash` service ({@see \Phalcon\Flash\Direct}).
 *
 * @method static string|null error(string $message)
 * @method static string|null notice(string $message)
 * @method static string|null success(string $message)
 * @method static string|null warning(string $message)
 * @method static string|null message(string $type, mixed $message)
 */
class Flash extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Services::FLASH;
    }
}

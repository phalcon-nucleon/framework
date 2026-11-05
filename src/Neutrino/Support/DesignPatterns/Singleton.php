<?php

declare(strict_types=1);

namespace Neutrino\Support\DesignPatterns;

use Phalcon\Di\Injectable;
use RuntimeException;

/**
 * Singleton design pattern: one instance per subclass.
 *
 * @phpstan-consistent-constructor
 */
abstract class Singleton extends Injectable
{
    /**
     * @var array<class-string<static>, static>
     */
    private static array $instances = [];

    protected function __construct() {}

    /**
     * @throws RuntimeException
     */
    private function __clone()
    {
        throw new RuntimeException('Try to clone Singleton instance.');
    }

    /**
     * Instantiates and returns the instance of the called class.
     */
    public static function instance(): static
    {
        /** @var static */
        return self::$instances[static::class] ??= new static();
    }
}

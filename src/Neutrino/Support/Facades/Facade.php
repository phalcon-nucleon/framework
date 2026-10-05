<?php

declare(strict_types=1);

/**
 * @author Taylor Otwell
 *
 * Adapted for Phalcon
 */

namespace Neutrino\Support\Facades;

use LogicException;
use Mockery;
use Mockery\MockInterface;
use Phalcon\Di\DiInterface;
use RuntimeException;

/**
 * Static proxy to a service of the container.
 *
 * `shouldReceive()` needs mockery/mockery, a development dependency: it is only loaded when called.
 */
abstract class Facade
{
    protected static ?DiInterface $di = null;

    /**
     * The resolved object instances, by service name.
     *
     * @var array<string, object>
     */
    protected static array $resolvedInstance = [];

    public static function clearResolvedInstances(): void
    {
        self::$resolvedInstance = [];
    }

    public static function setDependencyInjection(DiInterface $di): void
    {
        static::$di = $di;
    }

    /**
     * Hotswaps the underlying instance behind the facade.
     */
    public static function swap(object $instance): void
    {
        $name = self::accessorName();

        self::$resolvedInstance[$name] = $instance;

        self::replaceService($name, $instance);
    }

    /**
     * Initiates a mock expectation on the facade.
     *
     * @param array<string, mixed>|string ...$params
     *
     * @return \Mockery\Expectation|\Mockery\CompositeExpectation|\Mockery\HigherOrderMessage
     */
    public static function shouldReceive(array|string ...$params): mixed
    {
        if (!class_exists(Mockery::class)) {
            throw new LogicException(static::class . '::shouldReceive() requires mockery/mockery (composer require --dev mockery/mockery).');
        }

        $name = self::accessorName();

        $mock = static::isMock() ? self::$resolvedInstance[$name] : static::createFreshMockInstance();

        /** @var MockInterface $mock */
        return $mock->shouldReceive(...$params);
    }

    /**
     * Gets the root object behind the facade.
     */
    public static function getFacadeRoot(): object
    {
        return static::resolveFacadeInstance(static::getFacadeAccessor());
    }

    /**
     * Handles dynamic, static calls to the object.
     *
     * @param array<int|string, mixed> $args
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        return static::getFacadeRoot()->$method(...$args);
    }

    /**
     * Gets the name of the service in the container, or the object itself.
     */
    protected static function getFacadeAccessor(): string|object
    {
        throw new RuntimeException('Facade does not implement getFacadeAccessor method.');
    }

    /**
     * Creates a fresh mock instance and registers it as the service.
     */
    protected static function createFreshMockInstance(): MockInterface
    {
        $name = self::accessorName();

        self::$resolvedInstance[$name] = $mock = static::createMockInstance();

        $mock->shouldAllowMockingProtectedMethods();

        self::replaceService($name, $mock);

        return $mock;
    }

    protected static function createMockInstance(): MockInterface
    {
        $class = static::getMockableClass();

        return $class !== null ? Mockery::mock($class) : Mockery::mock();
    }

    /**
     * Determines whether a mock is set as the instance of the facade.
     */
    protected static function isMock(): bool
    {
        $name = self::accessorName();

        return isset(self::$resolvedInstance[$name]) && self::$resolvedInstance[$name] instanceof MockInterface;
    }

    /**
     * Gets the class to mock: the class of the current root, if it can be resolved.
     *
     * @return class-string|null
     */
    protected static function getMockableClass(): ?string
    {
        $name = self::accessorName();

        if (isset(self::$resolvedInstance[$name])) {
            return self::$resolvedInstance[$name]::class;
        }

        if (static::$di !== null && static::$di->has($name)) {
            return static::resolveFacadeInstance($name)::class;
        }

        return null;
    }

    /**
     * Resolves the facade root instance from the container.
     */
    protected static function resolveFacadeInstance(string|object $name): object
    {
        if (is_object($name)) {
            return $name;
        }

        if (isset(self::$resolvedInstance[$name])) {
            return self::$resolvedInstance[$name];
        }

        if (static::$di === null) {
            throw new RuntimeException('A facade root has not been set.');
        }

        $instance = static::$di->getShared($name);

        if (!is_object($instance)) {
            throw new RuntimeException('A facade root has not been set.');
        }

        return self::$resolvedInstance[$name] = $instance;
    }

    /**
     * Replaces the service in the container. The container caches resolved shared instances:
     * the service is removed first so that the new instance is the one resolved.
     */
    private static function replaceService(string $name, object $instance): void
    {
        if (static::$di === null) {
            return;
        }

        static::$di->remove($name);
        static::$di->setShared($name, $instance);
    }

    /**
     * Name under which the mock and the swapped instance are stored.
     */
    private static function accessorName(): string
    {
        $accessor = static::getFacadeAccessor();

        return is_object($accessor) ? $accessor::class : $accessor;
    }
}

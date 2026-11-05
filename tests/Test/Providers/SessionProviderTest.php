<?php

declare(strict_types=1);

namespace Test\Providers;

use Neutrino\Constants\Services;
use Neutrino\Providers\Session;
use Phalcon\Session\Adapter\Noop;
use Phalcon\Session\Adapter\Redis;
use Phalcon\Session\Adapter\Stream;
use Phalcon\Session\Bag;
use Phalcon\Session\Manager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use RuntimeException;

final class SessionProviderTest extends ProvidersTestCase
{
    public function testServices(): void
    {
        $di = $this->container([Session::class], ['session' => ['default' => 'noop', 'stores' => ['noop' => ['adapter' => 'noop']]]]);

        $this->assertTrue($di->getService(Services::SESSION)->isShared());
        $this->assertFalse($di->getService(Services::SESSION)->isResolved());
        $this->assertFalse($di->getService(Services::SESSION_BAG)->isShared());

        $session = $di->getShared(Services::SESSION);

        $this->assertInstanceOf(Manager::class, $session);
        $this->assertInstanceOf(Noop::class, $session->getAdapter());
        $this->assertSame($session, $di->getShared(Manager::class));

        $bag = $di->get(Services::SESSION_BAG, ['user']);
        $this->assertInstanceOf(Bag::class, $bag);
        $this->assertNotSame($bag, $di->get(Services::SESSION_BAG, ['name' => 'user']));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testStreamSessionIsStarted(): void
    {
        $dir = sys_get_temp_dir() . '/nucleon-session-' . getmypid();
        @mkdir($dir);

        $di = $this->container([Session::class], ['session' => [
            'default' => 'files',
            'stores'  => ['files' => ['adapter' => 'stream', 'name' => 'NUCLEON', 'options' => ['savePath' => $dir, 'uniqueId' => 'app']]],
        ]]);

        $session = $di->getShared(Services::SESSION);

        $this->assertInstanceOf(Manager::class, $session);
        $this->assertInstanceOf(Stream::class, $session->getAdapter());
        $this->assertTrue($session->exists());
        $this->assertSame('NUCLEON', $session->getName());

        $session->set('key', 'value');
        $this->assertSame('value', $session->get('key'));
        $this->assertSame('value', $_SESSION['app#key'] ?? null);

        $bag = $di->get(Services::SESSION_BAG, ['bag']);
        $bag->set('a', 1);
        $this->assertSame(['a' => 1], $session->get('bag'));

        $session->destroy();
        array_map(unlink(...), glob($dir . '/*') ?: []);
        rmdir($dir);
    }

    public function testSingleStore(): void
    {
        $di = $this->container([Session::class], ['session' => ['adapter' => 'noop']]);

        $this->assertInstanceOf(Noop::class, $di->getShared(Services::SESSION)->getAdapter());
    }

    public function testCustomHandler(): void
    {
        $di = $this->container([Session::class], ['session' => ['adapter' => StubSessionHandler::class, 'options' => ['a' => 1]]]);

        $handler = $di->getShared(Services::SESSION)->getAdapter();

        $this->assertInstanceOf(StubSessionHandler::class, $handler);
        $this->assertSame(['a' => 1], $handler->options);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function redisAdapters(): iterable
    {
        yield 'name' => ['redis'];
        yield '1.3 class name' => [Redis::class];
    }

    #[DataProvider('redisAdapters')]
    public function testRedis(string $name): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('The redis extension is not loaded.');
        }
        $host = getenv('REDIS_HOST');
        if (!is_string($host) || $host === '') {
            $this->markTestSkipped('No Redis server (REDIS_HOST).');
        }

        $di = $this->container([Session::class], ['session' => ['adapter' => $name, 'options' => ['host' => $host, 'prefix' => 'nucleon-test-']]]);
        $adapter = $di->getShared(Services::SESSION)->getAdapter();

        $this->assertInstanceOf(Redis::class, $adapter);
        $this->assertTrue($adapter->write('id', 'data'));
        $this->assertSame('data', $adapter->read('id'));
        $this->assertTrue($adapter->destroy('id'));
    }

    /**
     * Built without connecting: no memcached server needed.
     */
    public function testLibmemcachedClassName(): void
    {
        $di = $this->container([Session::class], ['session' => ['adapter' => \Phalcon\Session\Adapter\Libmemcached::class]]);

        $this->assertInstanceOf(\Phalcon\Session\Adapter\Libmemcached::class, $di->getShared(Services::SESSION)->getAdapter());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidConfigs(): iterable
    {
        yield 'unknown store' => [['default' => 'foo', 'stores' => ['files' => ['adapter' => 'stream']]], 'Session store "foo" not found in stores.'];
        yield 'no config' => [[], 'Session store "" not found in stores.'];
        yield 'no adapter' => [['adapter' => ''], 'Session: no adapter.'];
        yield 'Files' => [['adapter' => 'Files'], 'Session adapter "Files" was removed with Phalcon 5: use "stream".'];
        yield 'unknown adapter' => [['adapter' => 'NoValidClass'], 'Session adapter "NoValidClass" not found'];
        yield 'not a handler' => [['adapter' => \stdClass::class], 'Session adapter "stdClass" not found'];
        yield 'construction failure' => [['adapter' => StubSessionHandler::class, 'options' => ['fail' => true]], 'Session adapter "' . StubSessionHandler::class . '" construction failed: fail'];
    }

    /**
     * @param array<string, mixed> $session
     */
    #[DataProvider('invalidConfigs')]
    public function testInvalidConfig(array $session, string $message): void
    {
        $di = $this->container([Session::class], ['session' => $session]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        $di->getShared(Services::SESSION);
    }
}

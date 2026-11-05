<?php

declare(strict_types=1);

namespace Test\Cache;

use Neutrino\Cache\CacheStrategy;
use Neutrino\Config\Config;
use Neutrino\Constants\Services;
use Neutrino\Foundation\ProviderRegistrar;
use Neutrino\Providers\Cache as CacheProvider;
use Neutrino\Support\Facades\Cache;
use Phalcon\Cache\Adapter\Apcu;
use Phalcon\Cache\Adapter\Memory;
use Phalcon\Cache\Adapter\Redis;
use Phalcon\Cache\Adapter\Stream;
use Phalcon\Cache\Cache as PhalconCache;
use Phalcon\Di\Di;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Test\TestCase\TestCase;
use Test\TestCase\UseCaches;

final class CacheStrategyTest extends TestCase
{
    use UseCaches;

    public function testServices(): void
    {
        $di = $this->getDI();

        $this->assertInstanceOf(CacheStrategy::class, $di->getShared(Services::CACHE));
        $this->assertTrue($di->getService(Services::CACHE . '.memory')->isShared());
        $this->assertSame($di->getShared(Services::CACHE . '.file'), Cache::uses('file'));
    }

    public function testDefaultStore(): void
    {
        $this->assertTrue(Cache::set('key', ['a' => 1]));
        $this->assertTrue(Cache::has('key'));
        $this->assertSame(['a' => 1], Cache::get('key'));
        $this->assertSame(['a' => 1], $this->getDI()->getShared(Services::CACHE . '.memory')->get('key'));

        $this->assertTrue(Cache::delete('key'));
        $this->assertFalse(Cache::has('key'));
        $this->assertSame('default', Cache::get('key', 'default'));
    }

    public function testUses(): void
    {
        $this->assertInstanceOf(Memory::class, Cache::uses()->getAdapter());

        Cache::set('key', 'memory');

        $file = Cache::uses('file');
        $this->assertInstanceOf(Stream::class, $file->getAdapter());
        $this->assertSame($file, Cache::uses(), 'uses() keeps the store');
        $this->assertNull(Cache::get('key'));

        Cache::set('key', 'file');
        $this->assertSame('file', Cache::uses('file')->get('key'));
        $this->assertSame('memory', Cache::uses('memory')->get('key'));
    }

    public function testUnsupportedStore(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('nope unsupported');

        Cache::uses('nope');
    }

    /**
     * @return iterable<string, array{string, string, mixed}>
     */
    public static function serializers(): iterable
    {
        yield 'php' => ['file', 'php', ['a' => 1]];
        yield 'json' => ['fast', 'json', (object) ['a' => 1]];
        yield 'base64' => ['slow', 'base64', 'text'];
    }

    #[DataProvider('serializers')]
    public function testSerializers(string $store, string $serializer, mixed $value): void
    {
        $cache = Cache::uses($store);

        $this->assertInstanceOf(PhalconCache::class, $cache);
        $this->assertSame($serializer, $cache->getAdapter()->getDefaultSerializer());

        $this->assertTrue($cache->set('serialized', $value));
        $this->assertEquals($value, $cache->get('serialized'));
    }

    public function testTtl(): void
    {
        $cache = Cache::uses('file');

        $this->assertTrue($cache->set('key', 'value', 10));
        $this->assertTrue($cache->has('key'));

        // PSR-16: a ttl of 0 or less removes the item.
        $cache->set('key', 'value', 0);
        $this->assertFalse($cache->has('key'));

        $cache->set('key', 'value', new \DateInterval('PT1H'));
        $this->assertTrue($cache->has('key'));
    }

    public function testMultipleKeys(): void
    {
        $this->assertTrue(Cache::setMultiple(['a' => 1, 'b' => 2]));
        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 'none'], Cache::getMultiple(['a', 'b', 'c'], 'none'));

        $this->assertTrue(Cache::deleteMultiple(['a']));
        $this->assertFalse(Cache::has('a'));
        $this->assertTrue(Cache::has('b'));

        $this->assertTrue(Cache::clear());
        $this->assertFalse(Cache::has('b'));
    }

    public function testCustomAdapter(): void
    {
        $cache = Cache::uses('stub');

        $this->assertInstanceOf(StubAdapter::class, $cache->getAdapter());
        $this->assertSame('stub-', $cache->getAdapter()->getPrefix());

        $cache->set('key', 'stub');
        $this->assertSame('stub', Cache::get('key'));
    }

    public function testCallsAreForwardedToTheStore(): void
    {
        $this->assertInstanceOf(Memory::class, Cache::getAdapter());
    }

    public function testApcu(): void
    {
        $this->checkExtension('apcu');
        if (!apcu_enabled()) {
            $this->markTestSkipped('APCu is disabled (apc.enable_cli).');
        }

        $cache = $this->store(['adapter' => 'apcu', 'options' => ['prefix' => 'nucleon-test-' . getmypid() . '-']]);

        $this->assertInstanceOf(Apcu::class, $cache->getAdapter());
        $this->assertRoundTrip($cache);
    }

    public function testRedis(): void
    {
        $this->checkExtension('redis');
        $host = getenv('REDIS_HOST');
        if (!is_string($host) || $host === '') {
            $this->markTestSkipped('No Redis server (REDIS_HOST).');
        }

        $cache = $this->store(['adapter' => 'redis', 'serializer' => 'json', 'options' => ['host' => $host, 'prefix' => 'nucleon-test-' . getmypid() . '-']]);

        $this->assertInstanceOf(Redis::class, $cache->getAdapter());
        $this->assertRoundTrip($cache);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidStores(): iterable
    {
        yield '1.3 driver' => [['driver' => 'File', 'adapter' => 'Data'], 'the "driver" key of Nucleon 1.3 is no longer supported'];
        yield '1.3 backend' => [['backend' => 'Memory', 'frontend' => 'None'], 'the "backend" key of Nucleon 1.3'];
        yield 'no adapter' => [[], 'Cache store "invalid": no adapter.'];
        yield 'renamed adapter' => [['adapter' => 'File'], 'unknown adapter "File", use "stream".'];
        yield 'removed adapter' => [['adapter' => 'Mongo'], 'unknown adapter "Mongo". Supported adapters: apcu, libmemcached, memory'];
        yield 'not an adapter' => [['adapter' => \stdClass::class], 'unknown adapter "stdClass"'];
        yield 'missing option' => [['adapter' => 'stream'], "Cache store \"invalid\": The 'storageDir' must be specified"];
        yield 'invalid options' => [['adapter' => 'memory', 'options' => 'x'], '"options" must be an array'];
    }

    /**
     * @param array<string, mixed> $store
     */
    #[DataProvider('invalidStores')]
    public function testInvalidStore(array $store, string $message): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        $this->store($store);
    }

    public function testRegistrationBuildsNothing(): void
    {
        $di = new Di();
        $di->setShared(Services::CONFIG, new Config(['cache' => ['default' => 'a', 'stores' => [
            'a' => ['adapter' => 'memory'],
            'b' => ['adapter' => 'invalid'],
        ]]]));

        ProviderRegistrar::register($di, [CacheProvider::class]);

        $this->assertFalse($di->getService(Services::CACHE)->isResolved());
        $this->assertFalse($di->getService(Services::CACHE . '.a')->isResolved());
        $this->assertTrue($di->has(Services::CACHE . '.b'));

        $services = (new \Neutrino\Support\IdeHelper\Generator($di))->services();
        $this->assertSame(CacheStrategy::class, $services[Services::CACHE]);
        $this->assertSame(PhalconCache::class, $services[Services::CACHE . '.b']);
        $this->assertFalse($di->getService(Services::CACHE)->isResolved(), 'The IDE helpers build nothing.');

        $di->getShared(Services::CACHE)->set('key', 'value');

        $this->assertTrue($di->getService(Services::CACHE . '.a')->isResolved());
        $this->assertFalse($di->getService(Services::CACHE . '.b')->isResolved());
    }

    public function testNoStores(): void
    {
        $di = new Di();
        $di->setShared(Services::CONFIG, new Config([]));

        ProviderRegistrar::register($di, [CacheProvider::class]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(CacheStrategy::class . ' : no default adapter.');

        $di->getShared(Services::CACHE)->get('key');
    }

    /**
     * @param array<string, mixed> $config
     */
    private function store(array $config): PhalconCache
    {
        $provider = new CacheProvider();
        $provider->setDI($this->getDI());

        $cache = $provider->makeStore('invalid', $config);
        $this->assertInstanceOf(PhalconCache::class, $cache);

        return $cache;
    }

    private function assertRoundTrip(PhalconCache $cache): void
    {
        try {
            $this->assertTrue($cache->set('key', ['a' => 1], 10));
            $this->assertTrue($cache->has('key'));
            $this->assertEquals(['a' => 1], (array) $cache->get('key'));
            $this->assertTrue($cache->delete('key'));
            $this->assertFalse($cache->has('key'));
        } finally {
            $cache->delete('key');
        }
    }
}

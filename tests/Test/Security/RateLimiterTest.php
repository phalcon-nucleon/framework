<?php

declare(strict_types=1);

namespace Test\Security;

use Neutrino\Config\Config;
use Neutrino\Constants\Services;
use Neutrino\Foundation\ProviderRegistrar;
use Neutrino\Providers\Cache;
use Neutrino\Security\RateLimiter;
use Phalcon\Di\Di;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RateLimiterTest extends TestCase
{
    private int $now = 1_000_000;

    private string $dir = '';

    private string $prefix = '';

    private Di $di;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/nucleon-throttle-' . getmypid();
        $this->prefix = 'nucleon-test-' . getmypid() . '-' . bin2hex(random_bytes(3)) . '-';
        @mkdir($this->dir);

        $stores = [
            'memory' => ['adapter' => 'memory'],
            'stream' => ['adapter' => 'stream', 'options' => ['storageDir' => $this->dir]],
            'apcu'   => ['adapter' => 'apcu', 'options' => ['prefix' => $this->prefix]],
        ];
        if (is_string(getenv('REDIS_HOST')) && getenv('REDIS_HOST') !== '') {
            $stores['redis'] = ['adapter' => 'redis', 'options' => ['host' => getenv('REDIS_HOST'), 'prefix' => $this->prefix]];
        }

        $this->di = new Di();
        Di::setDefault($this->di);
        $this->di->setShared(Services::CONFIG, new Config(['cache' => ['default' => 'memory', 'stores' => $stores]]));
        ProviderRegistrar::register($this->di, [Cache::class]);
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->dir));
        Di::reset();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function stores(): iterable
    {
        yield 'memory' => ['memory'];
        yield 'stream' => ['stream'];
        yield 'apcu' => ['apcu'];
        yield 'redis' => ['redis'];
    }

    #[DataProvider('stores')]
    public function testFixedWindow(string $store): void
    {
        $limiter = $this->limiter($store);

        $this->assertSame(0, $limiter->attempts('key'));
        $this->assertSame(3, $limiter->retriesLeft('key', 3));
        $this->assertSame(0, $limiter->availableIn('key'));

        $this->assertSame(1, $limiter->hit('key', 60));
        $this->now += 30;
        $this->assertSame(2, $limiter->hit('key', 60));
        $this->assertSame(3, $limiter->hit('key', 60));

        $this->assertSame(3, $limiter->attempts('key'));
        $this->assertTrue($limiter->tooManyAttempts('key', 3));
        $this->assertFalse($limiter->tooManyAttempts('key', 4));
        $this->assertSame(0, $limiter->retriesLeft('key', 3));
        $this->assertSame(30, $limiter->availableIn('key'), 'The hits do not extend the window (1.3: 60).');
        $this->assertSame(0, $limiter->attempts('other'));

        // End of the window: counted from zero again, even if a client keeps hitting.
        $this->now += 30;
        $this->assertSame(0, $limiter->attempts('key'));
        $this->assertFalse($limiter->tooManyAttempts('key', 3));
        $this->assertSame(1, $limiter->hit('key', 60));
        $this->assertSame(60, $limiter->availableIn('key'));
    }

    #[DataProvider('stores')]
    public function testResetAndClear(string $store): void
    {
        $limiter = $this->limiter($store);
        $limiter->hit('key', 60);
        $limiter->hit('key', 60);

        $this->assertTrue($limiter->resetAttempts('key'));
        $this->assertSame(0, $limiter->attempts('key'));
        $this->assertSame(60, $limiter->availableIn('key'), 'The window is kept.');
        $this->assertSame(1, $limiter->hit('key', 60));

        $limiter->clear('key');
        $this->assertSame(0, $limiter->attempts('key'));
        $this->assertSame(0, $limiter->availableIn('key'));
    }

    public function testNamesAndKeysAreSeparated(): void
    {
        $a = new RateLimiter('a', 'memory', fn(): int => $this->now);
        $b = new RateLimiter('b', 'memory', fn(): int => $this->now);

        $a->hit('user@example.com {}()/\\@:', 60);

        $this->assertSame(1, $a->attempts('user@example.com {}()/\\@:'), 'Any key is accepted (hashed).');
        $this->assertSame(0, $b->attempts('user@example.com {}()/\\@:'));
    }

    public function testDedicatedStore(): void
    {
        $this->di->getShared(Services::CONFIG)->merge(new Config(['security' => ['throttle' => ['store' => 'stream']]]));

        (new RateLimiter('name', clock: fn(): int => $this->now))->hit('key', 60);

        $this->assertNotSame([], glob($this->dir . '/*') ?: [], 'Counted in the stream store.');
        $this->assertSame(0, (new RateLimiter('name', 'memory'))->attempts('key'));
    }

    public function testNoStore(): void
    {
        $this->di->remove(Services::CONFIG);
        $this->di->setShared(Services::CONFIG, new Config([]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('RateLimiter: no cache store');

        (new RateLimiter('name'))->attempts('key');
    }

    public function testConcurrentHitsOnRedis(): void
    {
        $this->requireStore('redis');
        $host = (string) getenv('REDIS_HOST');

        // The window exists: the processes only increment.
        $this->limiter('redis', real: true)->hit('key', 60);
        $limiter = new RateLimiter('concurrent', 'redis');
        $limiter->hit('key', 60);

        $processes = [];
        for ($i = 0; $i < 4; $i++) {
            $processes[] = proc_open([PHP_BINARY, __DIR__ . '/fixtures/hit.php', $host, $this->prefix, '50'], [], $pipes);
        }
        foreach ($processes as $process) {
            $this->assertIsResource($process);
            $this->assertSame(0, proc_close($process));
        }

        $this->assertSame(201, $limiter->attempts('key'));
        $limiter->clear('key');
    }

    public function testConcurrentAttemptsOnRedis(): void
    {
        $this->requireStore('redis');
        $host = (string) getenv('REDIS_HOST');
        $start = $this->dir . '/start';

        // The window exists: the processes only increment. 1 attempt counted, 5 left.
        $limiter = new RateLimiter('concurrent', 'redis');
        $this->assertSame(5, $limiter->attempt('key', 6));

        $processes = [];
        for ($i = 0; $i < 20; $i++) {
            $processes[] = proc_open([PHP_BINARY, __DIR__ . '/fixtures/attempt.php', $host, $this->prefix, '6', $start], [1 => ['pipe', 'w']], $pipes[$i]);
        }
        usleep(300_000);
        touch($start);

        $accepted = 0;
        foreach ($processes as $i => $process) {
            $this->assertIsResource($process);
            $accepted += (int) stream_get_contents($pipes[$i][1]);
            proc_close($process);
        }

        $this->assertSame(5, $accepted, 'Exactly the attempts left are accepted.');
        $limiter->clear('key');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function atomicStores(): iterable
    {
        yield 'apcu' => ['apcu'];
        yield 'redis' => ['redis'];
    }

    #[DataProvider('atomicStores')]
    public function testCounterRecreatedAfterAResetExpires(string $store): void
    {
        $limiter = $this->limiter($store, real: true);
        $limiter->hit('key', 60);
        $limiter->resetAttempts('key');

        $this->assertSame(1, $limiter->hit('key', 60));

        $counter = (new \ReflectionMethod(RateLimiter::class, 'keys'))->invoke($limiter, 'key')[0];
        $ttl = $store === 'redis'
            // The Redis client of Phalcon adds the prefix itself.
            ? $this->di->getShared(Services::CACHE . '.redis')->getAdapter()->getAdapter()->ttl($counter)
            : (apcu_key_info($this->prefix . $counter)['ttl'] ?? 0);

        $this->assertGreaterThan(0, $ttl, 'The counter expires with the window.');
        $this->assertLessThanOrEqual(60, $ttl);
        $limiter->clear('key');
    }

    private function limiter(string $store, bool $real = false): RateLimiter
    {
        $this->requireStore($store);

        return new RateLimiter('name', $store, $real ? null : fn(): int => $this->now);
    }

    private function requireStore(string $store): void
    {
        if ($store === 'apcu' && (!extension_loaded('apcu') || !apcu_enabled())) {
            $this->markTestSkipped('APCu is not available.');
        }
        if ($store === 'redis' && (!extension_loaded('redis') || !$this->di->has(Services::CACHE . '.redis'))) {
            $this->markTestSkipped('No Redis server (REDIS_HOST) or no redis extension.');
        }
    }
}

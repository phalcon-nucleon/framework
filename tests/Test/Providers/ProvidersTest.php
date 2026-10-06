<?php

declare(strict_types=1);

namespace Test\Providers;

use Neutrino\Constants\Services;
use Neutrino\Foundation\Bootstrap;
use Neutrino\Foundation\Http\Kernel as HttpKernel;
use Neutrino\Providers;
use Neutrino\Support\Provider;
use Neutrino\Support\SimpleProvider;
use Phalcon\Cli\Dispatcher as CliDispatcher;
use Phalcon\Cli\Router as CliRouter;
use Phalcon\Di\Injectable;
use RuntimeException;

/**
 * `Provider`, `SimpleProvider`, the `name => class` form, the console providers, and the lazy registration.
 */
final class ProvidersTest extends ProvidersTestCase
{
    public function testProvider(): void
    {
        $di = $this->container([StubProvider::class]);

        $this->assertFalse($di->getService('test')->isShared());
        $this->assertSame('test', $di->get('test'));
        $this->assertSame('test', $di->get('test.alias'));
    }

    public function testProviderWithoutName(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(StubProviderWithoutName::class . '::$name" isn\'t valid.');

        new StubProviderWithoutName();
    }

    public function testSimpleProvider(): void
    {
        $di = $this->container([StubSimpleProvider::class]);

        $this->assertInstanceOf(StubService::class, $di->get('test'));
        $this->assertNotSame($di->get('test'), $di->get('test'));
    }

    public function testSimpleProviderWithoutClass(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(StubSimpleProviderWithoutClass::class . '::$class" isn\'t valid.');

        new StubSimpleProviderWithoutClass();
    }

    public function testNameToClassRegistration(): void
    {
        $di = $this->container(['stub' => StubService::class]);

        $this->assertInstanceOf(StubService::class, $di->getShared('stub'));
        $this->assertSame($di->getShared('stub'), $di->getShared(StubService::class));
    }

    public function testCliProviders(): void
    {
        $di = $this->container([Providers\Cli\Router::class, Providers\Cli\Dispatcher::class]);
        $di->setShared(Services::APP, new \stdClass());

        $this->assertTrue($di->getService(Services::ROUTER)->isShared());
        $this->assertInstanceOf(CliRouter::class, $di->getShared(Services::ROUTER));
        $this->assertTrue($di->getService(Services::DISPATCHER)->isShared());
        $this->assertInstanceOf(CliDispatcher::class, $di->getShared(Services::DISPATCHER));
    }

    /**
     * No service is built at boot: the infrastructure services are built on their first use.
     */
    public function testNoServiceIsBuiltAtBoot(): void
    {
        $config = new \Neutrino\Config\Config([
            'cache'   => ['default' => 'memory', 'stores' => ['memory' => ['adapter' => 'memory'], 'file' => ['adapter' => 'stream']]],
            'session' => ['adapter' => 'noop'],
            'log'     => ['adapters' => ['noop']],
            'app'     => ['key' => 'key'],
            'auth'    => ['model' => \Neutrino\Foundation\Auth\User::class],
        ]);

        $kernel = (new Bootstrap($config))->make(StubKernelWithInfrastructure::class);
        $kernel->boot();

        $di = $kernel->getDI();

        foreach ([Services::CACHE, 'cache.memory', 'cache.file', Services::SESSION, Services::LOGGER, Services::FLASH_SESSION, Services::CRYPT, Services::SECURITY, Services::FILTER, Services::ESCAPER, Services::ANNOTATIONS, Services::AUTH] as $service) {
            $this->assertTrue($di->has($service), $service);
            $this->assertFalse($di->getService($service)->isResolved(), "$service is built at boot.");
        }
    }
}

final class StubKernelWithInfrastructure extends HttpKernel
{
    protected array $providers = [
        Providers\Logger::class,
        Providers\Cache::class,
        Providers\Session::class,
        Providers\Flash::class,
        Providers\FlashSession::class,
        Providers\Crypt::class,
        Providers\Security::class,
        Providers\Filter::class,
        Providers\Escaper::class,
        Providers\Annotations::class,
        Providers\Auth::class,
    ];

    public function registerRoutes(): void {}
}

final class StubService extends Injectable {}

final class StubProvider extends Provider
{
    protected string $name = 'test';

    protected array $aliases = ['test.alias'];

    protected function register(): string
    {
        return 'test';
    }
}

final class StubProviderWithoutName extends Provider
{
    protected function register(): null
    {
        return null;
    }
}

final class StubSimpleProvider extends SimpleProvider
{
    protected string $name = 'test';

    protected string $class = StubService::class;
}

final class StubSimpleProviderWithoutClass extends SimpleProvider
{
    protected string $name = 'test';
}

<?php

declare(strict_types=1);

namespace Test\Http;

use Neutrino\Config\Config;
use Neutrino\Constants\Services;
use Neutrino\Foundation\ProviderRegistrar;
use Neutrino\Providers;
use Neutrino\Support\IdeHelper\Generator;
use Phalcon\Di\FactoryDefault;
use Phalcon\Events\Manager;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\Router;
use Phalcon\Mvc\Url;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HttpProvidersTest extends TestCase
{
    protected function tearDown(): void
    {
        FactoryDefault::reset();
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string, string}>
     */
    public static function urlConfigs(): iterable
    {
        yield 'base uri only' => [['base_uri' => '/app/'], '/app/', '/app/'];
        yield 'static base uri' => [['base_uri' => '/app/', 'static_base_uri' => 'https://cdn.example.com/'], '/app/', 'https://cdn.example.com/'];
        yield 'no app config' => [[], '/', '/'];
    }

    /**
     * @param array<string, mixed> $app
     */
    #[DataProvider('urlConfigs')]
    public function testUrl(array $app, string $baseUri, string $staticBaseUri): void
    {
        $di = $this->container($app === [] ? [] : ['app' => $app]);

        $url = $di->getShared(Services::URL);

        $this->assertInstanceOf(Url::class, $url);
        $this->assertSame($url, $di->getShared(Url::class));
        $this->assertSame($baseUri, $url->getBaseUri());
        $this->assertSame($staticBaseUri, $url->getStaticBaseUri());
    }

    public function testRouterHasNoDefaultRoutes(): void
    {
        $router = $this->container()->getShared(Services::ROUTER);

        $this->assertInstanceOf(Router::class, $router);
        $this->assertSame([], $router->getRoutes());
    }

    public function testDispatcherUsesTheSharedEventsManager(): void
    {
        $di = $this->container();
        $em = new Manager();
        $di->setShared(Services::EVENTS_MANAGER, $em);

        $dispatcher = $di->getShared(Services::DISPATCHER);

        $this->assertInstanceOf(Dispatcher::class, $dispatcher);
        $this->assertSame($em, $dispatcher->getEventsManager());
    }

    public function testIdeHelpersKnowTheServicesWithoutBuildingThem(): void
    {
        $services = (new Generator($this->container()))->services();

        $this->assertSame(Router::class, $services[Services::ROUTER]);
        $this->assertSame(Dispatcher::class, $services[Services::DISPATCHER]);
        $this->assertSame(Url::class, $services[Services::URL]);
        $this->assertSame(\Phalcon\Http\Response\Cookies::class, $services[Services::COOKIES]);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function container(array $config = []): FactoryDefault
    {
        $di = new FactoryDefault();
        $di->setShared(Services::CONFIG, new Config($config));

        ProviderRegistrar::register($di, [
            Providers\Url::class,
            Providers\Cookies::class,
            Providers\Http\Router::class,
            Providers\Http\Dispatcher::class,
        ]);

        return $di;
    }
}

<?php

declare(strict_types=1);

namespace Test\Foundation;

use Fake\Kernels\Cli\StubKernelCli;
use Fake\Kernels\Cli\StubKernelCliEmpty;
use Fake\Kernels\Http\StubKernelHttp;
use Fake\Kernels\Http\StubKernelHttpEmpty;
use Fake\Kernels\Micro\StubKernelMicro;
use Neutrino\Constants\Events\Kernel as KernelEvents;
use Neutrino\Constants\Services;
use Neutrino\Foundation\Bootstrap;
use Neutrino\Foundation\Http\Kernel as HttpKernel;
use Neutrino\Interfaces\Kernelable;
use Neutrino\Support\Facades\Facade;
use Neutrino\Support\Facades\Router;
use Phalcon\Config\Config;
use Phalcon\Di\Di;
use Phalcon\Events\Event;
use Phalcon\Events\Manager;
use Phalcon\Http\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class BootstrapTest extends TestCase
{
    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Di::reset();
        RecordingKernel::$calls = [];
    }

    /**
     * @return iterable<array{class-string<Kernelable>, list<string>}>
     */
    public static function stubKernels(): iterable
    {
        yield 'http' => [StubKernelHttp::class, [Services::ROUTER, Services::DISPATCHER, Services::URL, \Phalcon\Mvc\Router::class]];
        yield 'http empty' => [StubKernelHttpEmpty::class, []];
        yield 'cli' => [StubKernelCli::class, [Services::ROUTER, Services::DISPATCHER, Services\Cli::OUTPUT]];
        yield 'cli empty' => [StubKernelCliEmpty::class, []];
        yield 'micro' => [StubKernelMicro::class, [Services::ROUTER, Services::MICRO_ROUTER]];
    }

    /**
     * @param class-string<Kernelable> $kernelClass
     * @param list<string>             $services
     */
    #[DataProvider('stubKernels')]
    public function testStubKernelsBoot(string $kernelClass, array $services): void
    {
        $config = self::config();

        $kernel = (new Bootstrap($config))->make($kernelClass);

        $this->assertInstanceOf($kernelClass, $kernel);

        $di = $kernel->getDI();
        $this->assertSame($di, Di::getDefault());
        $this->assertSame($kernel, $di->getShared(Services::APP));
        $this->assertSame($config, $di->getShared(Services::CONFIG));

        foreach ($services as $service) {
            $this->assertTrue($di->has($service), $service);
        }

        $kernel->boot();
        $kernel->terminate();
    }

    public function testFacadesUseTheKernelContainer(): void
    {
        $kernel = (new Bootstrap(self::config()))->make(StubKernelHttp::class);

        $this->assertSame($kernel->getDI()->getShared(Services::ROUTER), Router::getFacadeRoot());
    }

    public function testBootSequence(): void
    {
        (new Bootstrap(self::config()))->make(RecordingKernel::class);

        $this->assertSame(
            ['bootstrap', 'registerServices', 'registerMiddlewares', 'registerListeners', 'registerRoutes', 'registerModules'],
            RecordingKernel::$calls,
        );
    }

    public function testKernelEvents(): void
    {
        $kernel = (new Bootstrap(self::config()))->make(StubKernelHttpEmpty::class);

        $fired = [];
        $em = $kernel->getEventsManager();
        $this->assertInstanceOf(Manager::class, $em);
        $this->assertSame($em, $kernel->getDI()->getShared(Services::EVENTS_MANAGER));

        $em->attach('kernel', function (Event $event, mixed $source) use (&$fired): void {
            $fired[] = [$event->getType(), $source];
        });

        $kernel->boot();
        $kernel->terminate();

        $this->assertSame([[explode(':', KernelEvents::BOOT)[1], $kernel], [explode(':', KernelEvents::TERMINATE)[1], $kernel]], $fired);
    }

    public function testRunSendsTheResponse(): void
    {
        $bootstrap = new Bootstrap(self::config());
        $kernel = $bootstrap->make(RecordingKernel::class);

        ob_start();
        $bootstrap->run($kernel);
        $output = ob_get_clean();

        $this->assertSame('handled', $output);
        $this->assertTrue($kernel->response->isSent());
        $this->assertSame(['boot', 'handleIncoming', 'terminate'], array_slice(RecordingKernel::$calls, -3));
    }

    public function testDefaultContainer(): void
    {
        $di = new Di();
        Di::setDefault($di);

        $kernel = (new Bootstrap(self::config()))->make(DefaultContainerKernel::class);

        $this->assertSame($di, $kernel->getDI());
        $this->assertNull($kernel->getEventsManager());
    }

    public function testNoDefaultContainer(): void
    {
        Di::reset();

        $this->expectException(RuntimeException::class);

        (new Bootstrap(self::config()))->make(DefaultContainerKernel::class);
    }

    public function testCliOptions(): void
    {
        $kernel = (new Bootstrap(self::config()))->make(StubKernelCliEmpty::class);
        $kernel->setArgument(['nucleon', 'list', '-q', '--stats', '-h']);

        $this->assertTrue($kernel->isQuiet());
        $this->assertTrue($kernel->withStats());
        $this->assertTrue($kernel->isHelp());
        $this->assertSame(['list'], $kernel->getArguments());
    }

    private static function config(): Config
    {
        return new Config([
            'app'   => ['base_uri' => '/'],
            'cache' => ['stores' => []],
        ]);
    }
}

class RecordingKernel extends HttpKernel
{
    /** @var list<string> */
    public static array $calls = [];

    public Response $response;

    protected ?string $dependencyInjection = Di::class;

    public function bootstrap(Config $config): void
    {
        self::$calls[] = __FUNCTION__;
        parent::bootstrap($config);
    }

    public function registerServices(): void
    {
        self::$calls[] = __FUNCTION__;
        parent::registerServices();
    }

    public function registerMiddlewares(): void
    {
        self::$calls[] = __FUNCTION__;
        parent::registerMiddlewares();
    }

    public function registerListeners(): void
    {
        self::$calls[] = __FUNCTION__;
        parent::registerListeners();
    }

    public function registerRoutes(): void
    {
        self::$calls[] = __FUNCTION__;
    }

    public function registerModules(array $modules, bool $merge = false): static
    {
        self::$calls[] = __FUNCTION__;

        return parent::registerModules($modules, $merge);
    }

    public function boot(): void
    {
        self::$calls[] = __FUNCTION__;
        parent::boot();
    }

    public function handleIncoming(): mixed
    {
        self::$calls[] = __FUNCTION__;

        $this->response = new Response('handled');
        $this->response->setDI($this->getDI());

        return $this->response;
    }

    public function terminate(): void
    {
        self::$calls[] = __FUNCTION__;
        parent::terminate();
    }
}

class DefaultContainerKernel extends HttpKernel
{
    protected ?string $dependencyInjection = null;

    protected ?string $eventsManagerClass = null;

    public function registerRoutes(): void {}
}

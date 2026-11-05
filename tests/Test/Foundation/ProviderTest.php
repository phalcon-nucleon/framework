<?php

declare(strict_types=1);

namespace Test\Foundation;

use ArrayObject;
use Neutrino\Foundation\ProviderRegistrar;
use Neutrino\Module;
use Neutrino\Support\Provider;
use Neutrino\Support\SimpleProvider;
use Phalcon\Di\Di;
use Phalcon\Di\DiInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SplObjectStorage;
use stdClass;
use UnexpectedValueException;

final class ProviderTest extends TestCase
{
    private Di $di;

    protected function setUp(): void
    {
        $this->di = new Di();
        CountingProvider::$built = 0;
    }

    protected function tearDown(): void
    {
        Di::reset();
    }

    public function testClassStringIsASharedServiceUnderBothNames(): void
    {
        ProviderRegistrar::register($this->di, ['storage' => SplObjectStorage::class]);

        $service = $this->di->getShared('storage');

        $this->assertInstanceOf(SplObjectStorage::class, $service);
        $this->assertSame($service, $this->di->get('storage'));
        $this->assertSame($service, $this->di->getShared(SplObjectStorage::class));
    }

    public function testProviderIsResolvedLazily(): void
    {
        ProviderRegistrar::register($this->di, [CountingProvider::class]);

        $this->assertSame(0, CountingProvider::$built);
        $this->assertTrue($this->di->has('counting'));

        $service = $this->di->get('counting');

        $this->assertSame(1, CountingProvider::$built);
        $this->assertSame($service, $this->di->get('counting'));
        $this->assertSame($service, $this->di->get('counting.alias'));
        $this->assertSame(1, CountingProvider::$built);
        $this->assertSame($this->di, $service->di);
    }

    public function testNotSharedProvider(): void
    {
        ProviderRegistrar::register($this->di, [NotSharedProvider::class]);

        $this->assertNotSame($this->di->get('not-shared'), $this->di->get('not-shared'));
    }

    public function testSimpleProvider(): void
    {
        ProviderRegistrar::register($this->di, [SimpleArrayProvider::class, SimpleStdProvider::class]);

        $array = $this->di->get('array');
        $this->assertInstanceOf(ArrayObject::class, $array);
        $this->assertSame(['a' => 1], $array->getArrayCopy());
        $this->assertSame($array, $this->di->get(ArrayObject::class));

        $this->assertNotSame($this->di->get('std'), $this->di->get('std'));
        $this->assertSame(ArrayObject::class, (new SimpleArrayProvider())->getClass());
    }

    public function testInvalidProviders(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Provider "' . NamelessProvider::class . '::$name" isn\'t valid.');

        new NamelessProvider();
    }

    public function testInvalidSimpleProvider(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Provider "' . ClasslessProvider::class . '::$class" isn\'t valid.');

        new ClasslessProvider();
    }

    public function testNotProvidable(): void
    {
        $this->expectException(UnexpectedValueException::class);

        ProviderRegistrar::register($this->di, [stdClass::class]);
    }

    public function testModuleRegistersItsProviders(): void
    {
        $module = new StubModule();
        $module->registerAutoloaders($this->di);
        $module->registerServices($this->di);

        $this->assertSame($this->di, $module->initialised);
        $this->assertTrue($this->di->has('counting'));
        $this->assertTrue($this->di->has('storage'));
        $this->assertSame(0, CountingProvider::$built);
    }
}

class Built
{
    public function __construct(public readonly DiInterface $di) {}
}

class CountingProvider extends Provider
{
    public static int $built = 0;

    protected string $name = 'counting';

    protected bool $shared = true;

    protected array $aliases = ['counting.alias'];

    protected function register(): Built
    {
        self::$built++;

        return new Built($this->getDI());
    }
}

class NotSharedProvider extends Provider
{
    protected string $name = 'not-shared';

    protected function register(): stdClass
    {
        return new stdClass();
    }
}

class NamelessProvider extends Provider
{
    protected function register(): stdClass
    {
        return new stdClass();
    }
}

class SimpleArrayProvider extends SimpleProvider
{
    protected string $name = 'array';

    protected string $class = ArrayObject::class;

    protected bool $shared = true;

    protected array $aliases = [ArrayObject::class];

    protected array $options = [
        'arguments' => [
            ['type' => 'parameter', 'value' => ['a' => 1]],
        ],
    ];
}

class SimpleStdProvider extends SimpleProvider
{
    protected string $name = 'std';

    protected string $class = stdClass::class;
}

class ClasslessProvider extends SimpleProvider
{
    protected string $name = 'classless';
}

class StubModule extends Module
{
    public ?DiInterface $initialised = null;

    protected array $providers = [
        CountingProvider::class,
        'storage' => SplObjectStorage::class,
    ];

    public function initialise(DiInterface $container): void
    {
        $this->initialised = $container;
    }
}

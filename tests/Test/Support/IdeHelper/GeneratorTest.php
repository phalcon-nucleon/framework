<?php

declare(strict_types=1);

namespace Test\Support\IdeHelper;

use ArrayObject;
use Fake\Kernels\Http\StubKernelHttp;
use Neutrino\Foundation\Bootstrap;
use Neutrino\Foundation\ProviderRegistrar;
use Neutrino\Support\Facades\Facade;
use Neutrino\Support\Facades\Router;
use Neutrino\Support\IdeHelper\Generator;
use Neutrino\Support\Provider;
use Neutrino\Support\SimpleProvider;
use Phalcon\Config\Config;
use Phalcon\Di\Di;
use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\Router as MvcRouter;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SplObjectStorage;
use SplQueue;
use SplStack;
use stdClass;

final class GeneratorTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/nucleon-ide-helper-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        array_map(unlink(...), glob($this->dir . '/{,.}*.php', GLOB_BRACE) ?: []);
        @rmdir($this->dir);
        Facade::clearResolvedInstances();
        Di::reset();
    }

    public function testServicesClassesAreFoundWithoutBuildingThem(): void
    {
        $di = new Di();
        ProviderRegistrar::register($di, [
            'storage' => SplObjectStorage::class,
            TypedProvider::class,
            UntypedProvider::class,
            ArrayProvider::class,
        ]);
        $di->setShared('instance', new SplStack());
        $di->set('typedClosure', fn(): SplQueue => new SplQueue());
        $di->set('untypedClosure', fn() => new SplQueue());
        $di->set('failing', function (): never {
            throw new RuntimeException('not buildable');
        });
        $di->set('scalar', fn() => 'string');

        TypedProvider::$built = false;
        $services = (new Generator($di))->services();

        $this->assertSame([
            ArrayObject::class      => ArrayObject::class,
            SplObjectStorage::class => SplObjectStorage::class,
            'array'                 => ArrayObject::class,
            'instance'              => SplStack::class,
            'storage'               => SplObjectStorage::class,
            'typed'                 => SplStack::class,
            'typedClosure'          => SplQueue::class,
            'untyped'               => stdClass::class,
            'untypedClosure'        => SplQueue::class,
        ], $services);
        $this->assertFalse(TypedProvider::$built);
    }

    public function testRenderIdeHelper(): void
    {
        $di = new FactoryDefault();
        $di->setShared('micro.router', new stdClass());

        $content = (new Generator($di, [Router::class]))->renderIdeHelper();

        $this->assertValidPhp($content);
        $this->assertStringContainsString("namespace Neutrino\\Support\\Facades {\n", $content);
        $this->assertStringContainsString('    class Router extends \\Neutrino\\Support\\Facades\\Facade', $content);
        $this->assertStringContainsString('     * @method static \\Phalcon\\Mvc\\Router getFacadeRoot()', $content);
        // Phalcon 5 (extension) exposes the literal default, Phalcon 6 (PHP) the constant and the `mixed` types.
        $this->assertMatchesRegularExpression(
            '/@method static \\\\Phalcon\\\\Mvc\\\\Router\\\\RouteInterface add\\(string \\$pattern, (mixed )?\\$paths = null, (mixed )?\\$httpMethods = null, int \\$position = (1|\\\\Phalcon\\\\Mvc\\\\Router::POSITION_LAST)\\)/',
            $content,
        );
        $this->assertStringContainsString("namespace Phalcon\\Di {\n", $content);
        $this->assertStringContainsString('     * @property-read \\Phalcon\\Mvc\\Router $router', $content);
        $this->assertStringNotContainsString('$micro.router', $content);
    }

    public function testRenderPhpStormMeta(): void
    {
        $content = (new Generator(new FactoryDefault(), []))->renderPhpStormMeta();

        $this->assertValidPhp($content);
        $this->assertStringContainsString('override(\\Phalcon\\Di\\DiInterface::getShared(0), map([', $content);
        $this->assertStringContainsString("            'router' => \\Phalcon\\Mvc\\Router::class,", $content);
    }

    public function testWriteForTheFakeApplication(): void
    {
        $kernel = (new Bootstrap(new Config(['app' => ['base_uri' => '/'], 'cache' => ['stores' => []]])))->make(StubKernelHttp::class);

        $files = (new Generator($kernel->getDI()))->write($this->dir);

        $this->assertSame([$this->dir . '/_ide_helper.php', $this->dir . '/.phpstorm.meta.php'], $files);

        $ideHelper = (string) file_get_contents($files[0]);
        $this->assertValidPhp($ideHelper);
        $this->assertValidPhp((string) file_get_contents($files[1]));

        // Services of the kernel's providers and of FactoryDefault, and the Facades whose service is registered.
        $this->assertStringContainsString('@property-read \\' . MvcRouter::class . ' $router', $ideHelper);
        $this->assertStringContainsString('@property-read \\Phalcon\\Mvc\\Url $url', $ideHelper);
        $this->assertStringContainsString('@property-read \\Fake\\Kernels\\Http\\StubKernelHttp $application', $ideHelper);
        $this->assertStringContainsString('class Router extends', $ideHelper);
        $this->assertStringContainsString('class Url extends', $ideHelper);
        $this->assertStringContainsString('class Request extends', $ideHelper);
    }

    private function assertValidPhp(string $code): void
    {
        $file = tempnam(sys_get_temp_dir(), 'nucleon-lint');
        file_put_contents($file, $code);

        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $exitCode);
        unlink($file);

        $this->assertSame(0, $exitCode, implode("\n", $output));
    }
}

class TypedProvider extends Provider
{
    public static bool $built = false;

    protected string $name = 'typed';

    protected function register(): SplStack
    {
        self::$built = true;

        return new SplStack();
    }
}

class UntypedProvider extends Provider
{
    protected string $name = 'untyped';

    protected function register()
    {
        return new stdClass();
    }
}

class ArrayProvider extends SimpleProvider
{
    protected string $name = 'array';

    protected string $class = ArrayObject::class;

    protected array $aliases = [ArrayObject::class];

    protected array $options = ['arguments' => [['type' => 'parameter', 'value' => []]]];
}

<?php

declare(strict_types=1);

namespace Test\Providers;

use Neutrino\Constants\Services;
use Neutrino\Providers;
use Neutrino\Support\IdeHelper\Generator;
use Phalcon\Annotations\Adapter\AdapterInterface as AnnotationsAdapter;
use Phalcon\Annotations\Adapter\Apcu as AnnotationsApcu;
use Phalcon\Annotations\Adapter\Memory as AnnotationsMemory;
use Phalcon\Annotations\Adapter\Stream as AnnotationsStream;
use Phalcon\Encryption\Security;
use Phalcon\Filter\Filter;
use Phalcon\Flash\Direct;
use Phalcon\Flash\Session as FlashSession;
use Phalcon\Html\Escaper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use RuntimeException;

/**
 * Flash, security, filter, escaper and annotations providers.
 */
final class ServicesProvidersTest extends ProvidersTestCase
{
    private const array PROVIDERS = [
        Providers\Flash::class,
        Providers\FlashSession::class,
        Providers\Security::class,
        Providers\Filter::class,
        Providers\Escaper::class,
        Providers\Annotations::class,
        Providers\Session::class,
    ];

    /**
     * @return iterable<string, array{string, class-string, bool}>
     */
    public static function services(): iterable
    {
        yield Services::FLASH => [Services::FLASH, Direct::class, false];
        yield Services::FLASH_SESSION => [Services::FLASH_SESSION, FlashSession::class, true];
        yield Services::SECURITY => [Services::SECURITY, Security::class, true];
        yield Services::FILTER => [Services::FILTER, Filter::class, true];
        yield Services::ESCAPER => [Services::ESCAPER, Escaper::class, true];
        yield Services::ANNOTATIONS => [Services::ANNOTATIONS, AnnotationsMemory::class, true];
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('services')]
    public function testService(string $name, string $class, bool $shared): void
    {
        $di = $this->container(self::PROVIDERS);

        $this->assertSame($shared, $di->getService($name)->isShared());
        $this->assertInstanceOf($class, $di->get($name));

        // Resolution by class name.
        $alias = $class === AnnotationsMemory::class ? AnnotationsAdapter::class : $class;
        $this->assertInstanceOf($class, $di->get($alias));

        if ($shared) {
            $this->assertSame($di->getShared($name), $di->getShared($alias));
        }
    }

    public function testIdeHelpersKnowTheServices(): void
    {
        $services = (new Generator($this->container(self::PROVIDERS)))->services();

        $this->assertSame(Direct::class, $services[Services::FLASH]);
        $this->assertSame(FlashSession::class, $services[Services::FLASH_SESSION]);
        $this->assertSame(Security::class, $services[Services::SECURITY]);
        $this->assertSame(Filter::class, $services[Services::FILTER]);
        $this->assertSame(Escaper::class, $services[Services::ESCAPER]);
        $this->assertSame(\Phalcon\Session\Manager::class, $services[Services::SESSION]);
        $this->assertSame(\Phalcon\Session\Bag::class, $services[Services::SESSION_BAG]);
    }

    public function testFlash(): void
    {
        $di = $this->container(self::PROVIDERS);
        $flash = $di->get(Services::FLASH);

        $this->assertNotSame($flash, $di->get(Services::FLASH));
        $this->assertSame($di->getShared(Services::ESCAPER), $flash->getEscaperService());
        $this->assertSame('<div class="errorMessage">a &lt;b&gt;</div>' . PHP_EOL, $flash->error('a <b>'));
        $this->assertSame('', $this->printed(static fn() => $flash->error('a')), 'Messages are returned, not printed.');
    }

    public function testFilter(): void
    {
        $filter = $this->container(self::PROVIDERS)->getShared(Services::FILTER);

        $this->assertSame(12, $filter->sanitize('12abc', 'int'));
        $this->assertSame('abc', $filter->sanitize(' abc ', 'trim'));
    }

    public function testHashingDoesNotStartTheSession(): void
    {
        $di = $this->container(self::PROVIDERS, ['session' => ['adapter' => 'noop']]);
        $security = $di->getShared(Services::SECURITY);

        $hash = $security->hash('password');

        $this->assertTrue($security->checkHash('password', $hash));
        $this->assertFalse($security->checkHash('wrong', $hash));
        $this->assertFalse($di->getService(Services::SESSION)->isResolved());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testCsrfToken(): void
    {
        $di = $this->container(self::PROVIDERS, ['session' => ['adapter' => 'noop']]);
        $security = $di->getShared(Services::SECURITY);

        $key = $security->getTokenKey();
        $token = $security->getToken();

        $this->assertIsString($key);
        $this->assertIsString($token);
        $this->assertTrue($di->getShared(Services::SESSION)->exists());

        $this->assertTrue($security->checkToken($key, $token, false));
        $this->assertFalse($security->checkToken($key, 'wrong', false));

        $_POST[$key] = $token;
        $this->assertTrue($security->checkToken());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testFlashSession(): void
    {
        $di = $this->container(self::PROVIDERS, ['session' => ['adapter' => 'noop']]);
        $flash = $di->getShared(Services::FLASH_SESSION);

        $this->assertFalse($di->getService(Services::SESSION)->isResolved());

        $flash->success('saved');

        $this->assertTrue($di->getService(Services::SESSION)->isResolved());
        $this->assertSame(['success' => ['saved']], $flash->getMessages());
        $this->assertFalse($flash->has());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, class-string}>
     */
    public static function annotationsAdapters(): iterable
    {
        yield 'default' => [[], AnnotationsMemory::class];
        yield 'memory' => [['adapter' => 'memory'], AnnotationsMemory::class];
        yield 'apcu' => [['adapter' => 'apcu', 'options' => ['prefix' => 'app']], AnnotationsApcu::class];
        yield 'stream' => [['adapter' => 'stream', 'options' => ['annotationsDir' => '/tmp/']], AnnotationsStream::class];
        yield 'class' => [['adapter' => AnnotationsStream::class], AnnotationsStream::class];
    }

    /**
     * @param array<string, mixed> $config
     * @param class-string         $class
     */
    #[DataProvider('annotationsAdapters')]
    public function testAnnotationsAdapter(array $config, string $class): void
    {
        $di = $this->container([Providers\Annotations::class], $config === [] ? [] : ['annotations' => $config]);

        $this->assertInstanceOf($class, $di->getShared(Services::ANNOTATIONS));
    }

    public function testUnknownAnnotationsAdapter(): void
    {
        $di = $this->container([Providers\Annotations::class], ['annotations' => ['adapter' => 'files']]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Annotations: unknown adapter');

        $di->getShared(Services::ANNOTATIONS);
    }

    private function printed(callable $callable): string
    {
        ob_start();
        $callable();

        return (string) ob_get_clean();
    }
}

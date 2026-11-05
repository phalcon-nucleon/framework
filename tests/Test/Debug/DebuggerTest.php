<?php

declare(strict_types=1);

namespace Test\Debug;

use Neutrino\Constants\Services;
use Neutrino\Debug\DebugErrorLogger;
use Neutrino\Debug\Debugger;
use Neutrino\Debug\Highlight;
use Neutrino\Error\Error;
use Neutrino\Error\Handler;
use Phalcon\DebugBar\Collector\ExceptionsCollector;
use Phalcon\DebugBar\Collector\MessagesCollector;
use Phalcon\DebugBar\Debug;
use Phalcon\Events\Manager;
use Phalcon\Logger\Adapter\Noop;
use Phalcon\Logger\Logger;
use Test\TestCase\TestCase;

final class DebuggerTest extends TestCase
{
    protected function tearDown(): void
    {
        self::getConfig()->remove('debug');
        Debugger::reset();
        Highlight::$disabled = false;

        parent::tearDown();
    }

    public function testRegister(): void
    {
        $this->assertFalse(Debugger::isEnabled());

        Debugger::register($this->app);
        Debugger::register($this->app);

        $this->assertTrue(Debugger::isEnabled());
        $this->assertSame(1, array_count_values(Handler::getWriters())[DebugErrorLogger::class] ?? 0);
        $this->assertNotNull(Debug::getBar());
    }

    public function testDebugBarDisabledByTheConfig(): void
    {
        $this->getDI()->getShared(Services::CONFIG)->merge(new \Neutrino\Config\Config(['debug' => ['bar' => ['enabled' => false]]]));

        Debugger::register($this->app);

        $this->assertTrue(Debugger::isEnabled());
        $this->assertNull(Debug::getBar());
    }

    public function testServicesResolvedAfterwardsAreInstrumented(): void
    {
        Debugger::register($this->app);

        $this->getDI()->setShared('test.logger', fn(): Logger => new Logger('test', ['main' => new Noop()]));
        $this->getDI()->setShared('test.aware', fn(): Manager => new Manager());
        $this->getDI()->setShared('test.view', fn(): \Phalcon\Mvc\View\Simple => new \Phalcon\Mvc\View\Simple());

        $logger = $this->getDI()->getShared('test.logger');
        $view = $this->getDI()->getShared('test.view');

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertSame(['main', Debugger::LOGGER_ADAPTER], array_keys($logger->getAdapters()));
        $this->assertSame($this->app->getEventsManager(), $view->getEventsManager());
    }

    public function testErrorsPassedToTheDebugBar(): void
    {
        Debugger::register($this->app);
        $exception = new \RuntimeException('boom');

        (new DebugErrorLogger())->handle(Error::fromError(E_WARNING, 'a warning', BASE_PATH . '/app/file.php', 3));
        (new DebugErrorLogger())->handle(Error::fromException($exception));

        $bar = Debug::getBar();
        $this->assertNotNull($bar);
        $messages = $bar->getCollector(MessagesCollector::NAME)->collect();
        $exceptions = $bar->getCollector(ExceptionsCollector::NAME)->collect();

        $this->assertStringContainsString('Warning [E_WARNING]: a warning in app/file.php(3)', (string) json_encode($messages, JSON_UNESCAPED_SLASHES));
        $this->assertSame(1, $exceptions['badge'] ?? null);
        $this->assertCount(2, DebugErrorLogger::errors());
    }

    public function testErrorPageOfAnException(): void
    {
        $exception = new \RuntimeException('outer <b>message</b>', 12, new \LogicException('inner'));
        (new DebugErrorLogger())->handle($warning = Error::fromError(E_USER_WARNING, 'earlier warning', __FILE__, __LINE__));

        $page = Debugger::renderErrorPage(Error::fromException($exception));

        $this->assertStringContainsString('<title>RuntimeException</title>', $page);
        $this->assertStringContainsString('outer &lt;b&gt;message&lt;/b&gt;', $page);
        $this->assertStringContainsString('<h1>LogicException</h1>', $page);
        $this->assertStringContainsString('Previous exception #1', $page);
        $this->assertStringContainsString('<h2>PHP errors (1)</h2>', $page);
        $this->assertStringContainsString('earlier warning', $page);
        $this->assertStringContainsString('<span class="file">tests/Test/Debug/DebuggerTest.php</span>', str_replace(realpath(BASE_PATH . '/../../..') . '/', '', $page));
        $this->assertStringContainsString('class="hl-keyword"', $page);
        $this->assertStringContainsString('PHP ' . PHP_VERSION, $page);
        $this->assertSame([$warning], DebugErrorLogger::errors());
    }

    public function testErrorPageOfAFatalErrorWithoutHighlighting(): void
    {
        Highlight::$disabled = true;

        $page = Debugger::renderErrorPage(Error::fromError(E_ERROR, 'out of memory', __FILE__, __LINE__));

        $this->assertStringContainsString('<h1>Fatal error [E_ERROR]</h1>', $page);
        $this->assertStringContainsString('out of memory', $page);
        $this->assertStringNotContainsString('class="hl-keyword"', $page);
        $this->assertStringContainsString('<span class="hl-gutter hl-current">', $page);
        $this->assertStringNotContainsString('<h2>PHP errors', $page);
    }

    public function testRelativePath(): void
    {
        $this->assertSame('app/Http/Kernel.php', Debugger::relativePath(BASE_PATH . '/app/Http/Kernel.php'));
        $this->assertSame('/other/file.php', Debugger::relativePath('/other/file.php'));
    }
}

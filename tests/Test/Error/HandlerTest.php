<?php

declare(strict_types=1);

namespace Test\Error;

use Neutrino\Error\Error;
use Neutrino\Error\Handler;
use Neutrino\Error\Writer\Phplog;
use Neutrino\Error\Writer\Writable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class HandlerTest extends TestCase
{
    /** @var list<Error> */
    public static array $handled = [];

    private string $errorLog = '';

    protected function setUp(): void
    {
        self::$handled = [];
        StubFailingWriter::$instances = 0;
        $this->errorLog = (string) ini_get('error_log');
        ini_set('error_log', '/dev/null');
    }

    protected function tearDown(): void
    {
        Handler::unregister();
        Handler::setWriters([Phplog::class]);
        ini_set('error_log', $this->errorLog);
    }

    public function testWriters(): void
    {
        Handler::setWriters([Phplog::class, StubWriter::class]);
        Handler::addWriter(StubWriter::class);
        Handler::addWriter(StubFailingWriter::class);

        $this->assertSame([Phplog::class, StubWriter::class, StubFailingWriter::class], Handler::getWriters());
    }

    public function testHandleCallsEveryWriterOnce(): void
    {
        Handler::setWriters([StubFailingWriter::class, StubWriter::class]);
        $error = Error::fromError(E_USER_WARNING, 'msg', __FILE__, __LINE__);

        Handler::handle($error);
        Handler::handle($error);

        // The failing writer does not prevent the next one; the writers are built once.
        $this->assertSame([$error, $error], self::$handled);
        $this->assertSame(1, StubFailingWriter::$instances);
    }

    public function testHandleException(): void
    {
        Handler::setWriters([StubWriter::class]);
        $exception = new \Error('fatal');

        Handler::handleException($exception);

        $this->assertCount(1, self::$handled);
        $this->assertSame($exception, self::$handled[0]->exception);
        $this->assertSame(Error::EXCEPTION, self::$handled[0]->type);
    }

    public function testHandleErrorRespectsErrorReporting(): void
    {
        Handler::setWriters([StubWriter::class]);
        $level = error_reporting(E_ALL & ~E_USER_NOTICE);

        try {
            $this->assertFalse(Handler::handleError(E_USER_NOTICE, 'ignored', __FILE__, __LINE__));
            $this->assertTrue(Handler::handleError(E_USER_WARNING, 'handled', __FILE__, __LINE__));
        } finally {
            error_reporting($level);
        }

        $this->assertCount(1, self::$handled);
        $this->assertSame('handled', self::$handled[0]->message);
    }

    public function testRegister(): void
    {
        Handler::setWriters([StubWriter::class]);

        Handler::register();
        Handler::register();

        $this->assertTrue(Handler::isRegistered());

        $level = error_reporting(E_ALL);

        try {
            trigger_error('caught', E_USER_WARNING);
        } finally {
            error_reporting($level);
            Handler::unregister();
        }

        $this->assertFalse(Handler::isRegistered());
        $this->assertCount(1, self::$handled);
        $this->assertSame(E_USER_WARNING, self::$handled[0]->type);
        $this->assertSame(__FILE__, self::$handled[0]->file);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function processes(): iterable
    {
        yield 'warning' => ['register', 'warning', "E_USER_WARNING\n  Message : a warning\n"];
        yield 'uncaught exception' => ['register', 'exception', "Uncaught exception\n  Class : RuntimeException\n  Code : 0\n  Message : an exception\n"];
        // PHP 8.4: "Cannot redeclare function nucleon_twice()".
        yield 'compile error' => ['register', 'compile', "E_COMPILE_ERROR\n  Message : Cannot redeclare "];
        yield 'fatal error' => ['register', 'memory', "E_ERROR\n  Message : Allowed memory size of"];
        yield 'registered by the bootstrap' => ['bootstrap', 'compile', "E_COMPILE_ERROR\n  Message : Cannot redeclare "];
    }

    /**
     * An error in a process of its own: written by the Cli writer (output) and the Phplog writer (error output).
     */
    #[DataProvider('processes')]
    public function testErrorsInAProcess(string $mode, string $action, string $expected): void
    {
        [$output, $errors] = self::runProcess($mode, $action);

        $this->assertSame(1, substr_count($output, $expected), $output);
        $this->assertSame(1, substr_count($errors, $expected), $errors);
    }

    public function testErrorsExcludedInAProcess(): void
    {
        foreach (['silenced', 'unreported'] as $action) {
            [$output, $errors] = self::runProcess('register', $action);

            $this->assertSame("\nend of script\n", $output);
            $this->assertSame('', $errors);
        }
    }

    public function testRegistrationDisabledByTheConfig(): void
    {
        [$output] = self::runProcess('bootstrap-off', 'warning');

        $this->assertStringNotContainsString('E_USER_WARNING', $output);
        $this->assertStringContainsString('end of script', $output);
    }

    /**
     * @return array{string, string}
     */
    private static function runProcess(string $mode, string $action): array
    {
        $process = proc_open([PHP_BINARY, __DIR__ . '/fixtures/handler.php', $mode, $action], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

        if (!is_resource($process)) {
            throw new RuntimeException('Cannot run the process.');
        }

        $output = (string) stream_get_contents($pipes[1]);
        $errors = (string) stream_get_contents($pipes[2]);
        proc_close($process);

        return [$output, $errors];
    }
}

final class StubWriter implements Writable
{
    public function handle(Error $error): void
    {
        HandlerTest::$handled[] = $error;
    }
}

final class StubFailingWriter implements Writable
{
    public static int $instances = 0;

    public function __construct()
    {
        self::$instances++;
    }

    public function handle(Error $error): void
    {
        throw new RuntimeException('failing writer');
    }
}

<?php

declare(strict_types=1);

namespace Test\Cli\Tasks;

use Neutrino\Process\Process;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\Cli\CliTestCase;

final class ServerTaskTest extends CliTestCase
{
    private FakeProcess $process;

    protected function setUp(): void
    {
        parent::setUp();

        $this->process = new FakeProcess();
        $this->mockService(Process::class, $this->process);
    }

    public function testServerOnTheFirstFreePort(): void
    {
        $output = $this->runCommand('server:run');

        $this->assertMatchesRegularExpression('#\[OK\] http://127\.0\.0\.1:80\d\d#', $output);
        $this->assertStringContainsString('started', $output);
        $this->assertStringContainsString('[ERR] server suddenly stopped', $output);
        $this->assertSame(['start', 'watch', 'close'], $this->process->calls);
    }

    public function testServerOnAGivenHostAndPort(): void
    {
        $output = $this->runCommand('server:run --host=localhost --port=8765');

        $this->assertStringContainsString('[OK] http://localhost:8765', $output);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidOptions(): iterable
    {
        yield 'empty host' => ['--host', 'Host can\'t be empty'];
        yield 'invalid host' => ['--host=.example.com', 'Host [.example.com] is not valid.'];
        yield 'empty port' => ['--port', 'Port can\'t be empty'];
    }

    #[DataProvider('invalidOptions')]
    public function testInvalidOptions(string $options, string $message): void
    {
        $this->assertStringContainsString($message, $this->runCommand('server:run ' . $options));
        $this->assertSame([], $this->process->calls);
    }

    public function testPortAlreadyUsed(): void
    {
        $server = stream_socket_server('tcp://127.0.0.1:0');
        $this->assertIsResource($server);
        $port = (int) substr((string) strrchr((string) stream_socket_get_name($server, false), ':'), 1);

        try {
            $output = $this->runCommand('server:run --port=' . $port);
        } finally {
            fclose($server);
        }

        $this->assertStringContainsString("Port [$port] on host [127.0.0.1] is already used.", $output);
    }
}

/**
 * Stands for Neutrino\Process\Process (ported in E14).
 */
class FakeProcess
{
    /** @var list<string> */
    public array $calls = [];

    public function start(): void
    {
        $this->calls[] = 'start';
    }

    public function watch(callable $callback): void
    {
        $this->calls[] = 'watch';
        $callback("started\n", '');
    }

    public function close(): void
    {
        $this->calls[] = 'close';
    }
}

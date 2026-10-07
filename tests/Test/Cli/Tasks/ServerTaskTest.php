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

        $process = $this->process = new FakeProcess();
        // Not static: the container binds its closures.
        $this->getDI()->set(Process::class, function (array|string $command, ?string $cwd = null) use ($process): FakeProcess {
            return $process($command, $cwd);
        });
    }

    public function testServerOnTheFirstFreePort(): void
    {
        $output = $this->runCommand('server:run');

        $this->assertMatchesRegularExpression('#\[OK\] http://127\.0\.0\.1:80\d\d#', $output);
        $this->assertStringContainsString('started', $output);
        $this->assertStringContainsString('[ERR] server suddenly stopped', $output);
        $this->assertSame(['start', 'watch'], $this->process->calls);
        // A list: run without shell.
        $this->assertIsArray($this->process->command);
        [$php, $option, $address, $script] = $this->process->command;
        $this->assertSame([PHP_BINARY, '-S', 'app_dev.php'], [$php, $option, $script]);
        $this->assertStringContainsString('[OK] http://' . $address, $output);
        $this->assertSame(BASE_PATH . '/public', $this->process->cwd);
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
 * A process that is not run: records the calls of the task.
 */
final class FakeProcess extends Process
{
    /** @var list<string> */
    public array $calls = [];

    /** @var list<string>|string */
    public array|string $command = [];

    public ?string $cwd = null;

    public function __construct() {}

    public function __invoke(array|string $command, ?string $cwd = null): self
    {
        $this->command = $command;
        $this->cwd = $cwd;

        return $this;
    }

    public function start(): static
    {
        $this->calls[] = 'start';

        return $this;
    }

    public function watch(callable $callback, ?float $timeout = null): int
    {
        $this->calls[] = 'watch';
        $callback("started\n", '');

        return 0;
    }
}

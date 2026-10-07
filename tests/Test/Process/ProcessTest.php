<?php

declare(strict_types=1);

namespace Test\Process;

use Neutrino\Process\Exception\ProcessException;
use Neutrino\Process\Exception\ProcessFailedException;
use Neutrino\Process\Exception\ProcessTimedOutException;
use Neutrino\Process\Process;
use PHPUnit\Framework\TestCase;

final class ProcessTest extends TestCase
{
    public function testRun(): void
    {
        $process = new Process([PHP_BINARY, '-r', 'echo "out"; fwrite(STDERR, "err"); exit(3);']);

        $this->assertFalse($process->isStarted());
        $this->assertSame(3, $process->run());
        $this->assertFalse($process->isRunning());
        $this->assertSame(3, $process->getExitCode());
        $this->assertFalse($process->isSuccessful());
        $this->assertSame('out', $process->getOutput());
        $this->assertSame('err', $process->getErrorOutput());
        $this->assertIsInt($process->getPid());
    }

    public function testMustRun(): void
    {
        $this->assertTrue((new Process([PHP_BINARY, '-r', 'echo 1;']))->mustRun()->isSuccessful());

        try {
            (new Process([PHP_BINARY, '-r', 'echo "out"; fwrite(STDERR, "err"); exit(2);']))->mustRun();
            $this->fail('No exception');
        } catch (ProcessFailedException $e) {
            $this->assertSame(2, $e->getCode());
            $this->assertSame(2, $e->getProcess()->getExitCode());
            $this->assertStringContainsString('failed with exit code 2', $e->getMessage());
            $this->assertStringContainsString("Output:\nout", $e->getMessage());
            $this->assertStringContainsString("Error output:\nerr", $e->getMessage());
        }
    }

    /**
     * The arguments of a list are passed as they are: no shell interpretation.
     */
    public function testArgumentsWithoutShell(): void
    {
        $argument = 'a b; echo injected $(whoami) `id` | cat > /tmp/x "quoted" \'single\'';

        $process = new Process([PHP_BINARY, '-r', 'echo $argv[1];', $argument]);
        $process->mustRun();

        $this->assertSame($argument, $process->getOutput());
    }

    public function testShellCommand(): void
    {
        $process = new Process('echo "$NUCLEON_VAR" | tr a-z A-Z', env: ['NUCLEON_VAR' => 'shell']);

        $this->assertSame(0, $process->run());
        $this->assertSame("SHELL\n", $process->getOutput());
    }

    public function testCwdAndEnv(): void
    {
        putenv('NUCLEON_INHERITED=1');
        putenv('NUCLEON_REMOVED=1');

        try {
            $process = new Process([PHP_BINARY, '-r', 'echo getcwd(), "|", getenv("NUCLEON_ADDED"), "|", getenv("NUCLEON_INHERITED"), "|", var_export(getenv("NUCLEON_REMOVED"), true);'], sys_get_temp_dir(), ['NUCLEON_ADDED' => 'added', 'NUCLEON_REMOVED' => null]);
            $process->mustRun();
        } finally {
            putenv('NUCLEON_INHERITED');
            putenv('NUCLEON_REMOVED');
        }

        $this->assertSame(realpath(sys_get_temp_dir()) . '|added|1|false', $process->getOutput());
    }

    public function testInput(): void
    {
        $process = (new Process([PHP_BINARY, '-r', 'echo strtoupper(stream_get_contents(STDIN));']))->setInput('from a string');
        $this->assertSame('FROM A STRING', $process->mustRun()->getOutput());

        $stream = fopen('php://memory', 'w+');
        $this->assertIsResource($stream);
        fwrite($stream, 'from a stream');
        rewind($stream);

        $process = (new Process([PHP_BINARY, '-r', 'echo strtoupper(stream_get_contents(STDIN));']))->setInput($stream);
        $this->assertSame('FROM A STREAM', $process->mustRun()->getOutput());
    }

    public function testLargeOutput(): void
    {
        // 3 MB on each output: beyond the pipe buffers and the 1 MB kept in memory.
        $process = new Process([PHP_BINARY, '-r', 'for ($i = 0; $i < 3072; $i++) { echo str_repeat("o", 1024); fwrite(STDERR, str_repeat("e", 1024)); }']);

        $this->assertSame(0, $process->run());
        $this->assertSame(3 * 1024 * 1024, strlen($process->getOutput()));
        $this->assertSame(3 * 1024 * 1024, strlen($process->getErrorOutput()));
    }

    public function testIncrementalOutputAndWatch(): void
    {
        $chunks = [];
        $process = new Process([PHP_BINARY, '-r', 'echo "a"; usleep(200000); fwrite(STDERR, "b"); usleep(200000); echo "c";']);

        $this->assertSame(0, $process->run(static function (string $output, string $errorOutput) use (&$chunks): void {
            $chunks[] = [$output, $errorOutput];
        }));

        $this->assertSame([['a', ''], ['', 'b'], ['c', '']], $chunks);
        $this->assertSame('', $process->getIncrementalOutput());
        $this->assertSame('ac', $process->getOutput());
    }

    /**
     * 1.3: the condition of wait() was reversed, it returned while the delay was not over.
     */
    public function testWaitWithATimeout(): void
    {
        $process = (new Process([PHP_BINARY, '-r', 'usleep(500000); echo "done";']))->start();
        $start = microtime(true);

        $this->assertSame(0, $process->wait(5.0));
        $this->assertGreaterThan(0.4, microtime(true) - $start);
        $this->assertSame('done', $process->getOutput());

        $process = (new Process([PHP_BINARY, '-r', 'sleep(5);']))->start();
        $start = microtime(true);

        try {
            $process->wait(0.3);
            $this->fail('No timeout');
        } catch (ProcessTimedOutException $e) {
            $elapsed = microtime(true) - $start;
            $this->assertGreaterThanOrEqual(0.3, $elapsed);
            $this->assertLessThan(1.0, $elapsed);
            $this->assertSame($process, $e->getProcess());
            $this->assertStringContainsString('exceeded the timeout of 0.3 seconds', $e->getMessage());
            // Not stopped by wait().
            $this->assertTrue($process->isRunning());
        }

        $process->stop(0);
    }

    /**
     * 1.3: watch() added milliseconds to microtime(true), in seconds.
     */
    public function testWatchTimeoutInSeconds(): void
    {
        $process = (new Process([PHP_BINARY, '-r', 'while (true) { echo "."; usleep(50000); }']))->start();
        $start = microtime(true);

        try {
            $process->watch(static fn(): null => null, 0.3);
            $this->fail('No timeout');
        } catch (ProcessTimedOutException) {
            $this->assertLessThan(1.0, microtime(true) - $start);
        }

        $process->stop(0);
    }

    public function testRunStopsTheProcessAfterItsTimeout(): void
    {
        $process = new Process([PHP_BINARY, '-r', 'sleep(5);'], timeout: 0.2);

        try {
            $process->run();
            $this->fail('No timeout');
        } catch (ProcessTimedOutException) {
            $this->assertFalse($process->isRunning());
        }
    }

    public function testWaitUntil(): void
    {
        $process = (new Process([PHP_BINARY, '-r', 'usleep(100000); fwrite(STDERR, "ready\n"); sleep(5);']))->start();

        $this->assertTrue($process->waitUntil(static fn(string $output, string $errorOutput): bool => str_contains($errorOutput, 'ready'), 3.0));
        $this->assertTrue($process->isRunning());

        $process->stop(0);

        $ended = (new Process([PHP_BINARY, '-r', 'echo "x";']))->start();
        $this->assertFalse($ended->waitUntil(static fn(string $output): bool => str_contains($output, 'never'), 3.0));

        $slow = (new Process([PHP_BINARY, '-r', 'sleep(5);']))->start();

        try {
            $slow->waitUntil(static fn(): bool => false, 0.2);
            $this->fail('No timeout');
        } catch (ProcessTimedOutException) {
            $slow->stop(0);
        }
    }

    public function testStop(): void
    {
        $process = (new Process([PHP_BINARY, '-r', 'sleep(10);']))->start();
        $start = microtime(true);

        $this->assertSame(128 + Process::SIGTERM, $process->stop(2.0));
        $this->assertLessThan(1.0, microtime(true) - $start);
        $this->assertFalse($process->isRunning());
        $this->assertSame(128 + Process::SIGTERM, $process->stop());
        $this->assertNull((new Process(['true']))->stop());
    }

    /**
     * 1.3: stop() waited 1 000 times too long, and never killed a process that ignores SIGTERM.
     */
    public function testStopKillsAProcessIgnoringSigterm(): void
    {
        $process = (new Process(['sh', '-c', "trap '' TERM; echo ready; while :; do sleep 0.05; done"]))->start();
        $process->waitUntil(static fn(string $output): bool => str_contains($output, 'ready'), 3.0);
        $start = microtime(true);

        $this->assertSame(128 + Process::SIGKILL, $process->stop(0.3));

        $elapsed = microtime(true) - $start;
        $this->assertGreaterThanOrEqual(0.3, $elapsed);
        $this->assertLessThan(1.5, $elapsed);
    }

    public function testDestructorStopsTheProcess(): void
    {
        $process = (new Process([PHP_BINARY, '-r', 'sleep(10);']))->start();
        $pid = (int) $process->getPid();
        $start = microtime(true);

        unset($process);

        $this->assertLessThan(1.0, microtime(true) - $start);

        // Stopped and reaped by proc_close().
        if (function_exists('posix_kill')) {
            $this->assertFalse(posix_kill($pid, 0));
        } elseif (is_dir('/proc/' . getmypid())) {
            $this->assertDirectoryDoesNotExist('/proc/' . $pid);
        } else {
            $this->markTestIncomplete('Neither ext-posix nor /proc to check that the process is gone.');
        }
    }

    public function testStartErrors(): void
    {
        try {
            (new Process(['nucleon-missing-program']))->run();
            $this->fail('No exception');
        } catch (ProcessException $e) {
            $this->assertStringContainsString('Cannot start the process "\'nucleon-missing-program\'"', $e->getMessage());
        }

        try {
            (new Process([PHP_BINARY, '-v'], '/nucleon/missing/directory'))->run();
            $this->fail('No exception');
        } catch (ProcessException $e) {
            $this->assertInstanceOf(ProcessException::class, $e->getPrevious());
        }
    }

    public function testMisuses(): void
    {
        $process = new Process([PHP_BINARY, '-v']);

        try {
            $process->wait();
            $this->fail('No exception');
        } catch (ProcessException $e) {
            $this->assertStringContainsString('is not started', $e->getMessage());
        }

        $process->run();

        try {
            $process->setInput('late');
            $this->fail('No exception');
        } catch (ProcessException $e) {
            $this->assertStringContainsString('before the process starts', $e->getMessage());
        }

        $this->expectException(ProcessException::class);
        $this->expectExceptionMessage('is already started');

        $process->start();
    }
}

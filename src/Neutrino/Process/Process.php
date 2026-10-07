<?php

declare(strict_types=1);

namespace Neutrino\Process;

use Neutrino\Process\Exception\ProcessException;
use Neutrino\Process\Exception\ProcessFailedException;
use Neutrino\Process\Exception\ProcessTimedOutException;

/**
 * A system process (`proc_open`). The durations are in seconds.
 *
 * ```php
 * $code = (new Process(['git', 'status']))->run();
 * (new Process(['composer', 'dump-autoload']))->mustRun();
 *
 * $server = new Process([PHP_BINARY, '-S', '127.0.0.1:8000'], cwd: $dir, env: ['APP_ENV' => 'test']);
 * $server->start();
 * $server->waitUntil(fn (string $output, string $errorOutput): bool => str_contains($errorOutput, 'started'), 5.0);
 * $server->stop(2.0); // SIGTERM, then SIGKILL after 2 s
 * ```
 *
 * The outputs are written to temporary files: a process writing a lot never blocks.
 */
class Process
{
    public const int SIGTERM = 15;

    public const int SIGKILL = 9;

    /** Polling interval of the waits, in microseconds */
    private const int POLL = 10000;

    /** @var resource|null */
    private $process = null;

    /** @var array{1: resource, 2: resource}|null The outputs of the process, read */
    private ?array $streams = null;

    /** @var resource|string|null */
    private mixed $input = null;

    private ?int $pid = null;

    private ?int $exitCode = null;

    private string $output = '';

    private string $errorOutput = '';

    private int $incrementalOutput = 0;

    private int $incrementalErrorOutput = 0;

    /**
     * @param list<string>|string       $command A list: run without shell (recommended). A string: run by the shell,
     *                                           never with external data in it (command injection)
     * @param array<string, string|null> $env    Variables added to the environment of PHP (`null` removes one)
     * @param float|null                 $timeout Timeout of {@see self::run()}
     */
    public function __construct(
        private readonly array|string $command,
        private readonly ?string $cwd = null,
        private readonly ?array $env = null,
        private readonly ?float $timeout = null,
    ) {}

    public function __destruct()
    {
        $this->stop(0);
    }

    /**
     * The input of the process: a string, or a stream read up to its end.
     *
     * @param resource|string|null $input
     */
    public function setInput(mixed $input): static
    {
        if ($this->process !== null) {
            throw new ProcessException('The input must be set before the process starts.');
        }

        if ($input !== null && !is_string($input) && !is_resource($input)) {
            throw new ProcessException('The input of a process is a string or a stream.');
        }

        $this->input = $input;

        return $this;
    }

    public function start(): static
    {
        if ($this->process !== null) {
            throw new ProcessException('The process "' . $this->getCommandLine() . '" is already started.');
        }

        $input = fopen('php://temp/maxmemory:' . (1024 * 1024), 'w+') ?: throw new ProcessException('Cannot open a temporary stream.');

        if (is_string($this->input)) {
            fwrite($input, $this->input);
        } elseif (is_resource($this->input)) {
            stream_copy_to_stream($this->input, $input);
        }
        rewind($input);

        // The process appends to its own descriptors of the files: a read of the parent cannot move its writes.
        $files = [1 => self::temporaryFile(), 2 => self::temporaryFile()];
        $descriptors = [0 => $input, 1 => ['file', $files[1], 'a'], 2 => ['file', $files[2], 'a']];

        $error = null;
        set_error_handler(static function (int $type, string $message) use (&$error): bool {
            $error = $message;

            return true;
        });

        try {
            $process = proc_open($this->command, $descriptors, $pipes, $this->cwd, $this->environment(), ['bypass_shell' => true]);
            $streams = [1 => fopen($files[1], 'r'), 2 => fopen($files[2], 'r')];
        } finally {
            restore_error_handler();
            fclose($input);
            // Kept by the open descriptors (unix).
            @unlink($files[1]);
            @unlink($files[2]);
        }

        if (!is_resource($process) || $streams[1] === false || $streams[2] === false) {
            array_map(static fn(mixed $stream): bool => is_resource($stream) && fclose($stream), $streams);

            throw new ProcessException('Cannot start the process "' . $this->getCommandLine() . '"' . ($error === null ? '.' : ': ' . $error), 0, $error === null ? null : new ProcessException($error));
        }

        $this->process = $process;
        $this->streams = $streams;
        $this->pid = proc_get_status($process)['pid'];

        return $this;
    }

    /**
     * Starts the process and waits for its end; `$onOutput` receives the new output and error output.
     *
     * @param (callable(string, string): mixed)|null $onOutput
     *
     * @return int The exit code
     *
     * @throws ProcessTimedOutException Beyond the timeout of the process (it is stopped)
     */
    public function run(?callable $onOutput = null): int
    {
        $this->start();

        try {
            return $onOutput === null ? $this->wait($this->timeout) : $this->watch($onOutput, $this->timeout);
        } catch (ProcessTimedOutException $e) {
            $this->stop(0);

            throw $e;
        }
    }

    /**
     * Like {@see self::run()}, but throws when the exit code is not 0.
     *
     * @param (callable(string, string): mixed)|null $onOutput
     *
     * @throws ProcessFailedException
     */
    public function mustRun(?callable $onOutput = null): static
    {
        if ($this->run($onOutput) !== 0) {
            throw new ProcessFailedException($this);
        }

        return $this;
    }

    /**
     * Waits for the end of the process.
     *
     * @return int The exit code
     *
     * @throws ProcessTimedOutException The process is still running after `$timeout` (it is not stopped)
     */
    public function wait(?float $timeout = null): int
    {
        return $this->watch(static fn(): null => null, $timeout);
    }

    /**
     * Waits until a condition on the outputs (all of them so far) is true.
     *
     * @param callable(string, string): bool $condition
     *
     * @return bool `false` when the process ended before
     *
     * @throws ProcessTimedOutException The condition is still false after `$timeout`
     */
    public function waitUntil(callable $condition, ?float $timeout = null): bool
    {
        $this->assertStarted();
        $deadline = self::deadline($timeout);

        while (true) {
            $running = $this->isRunning();

            if ($condition($this->getOutput(), $this->getErrorOutput())) {
                return true;
            }

            if (!$running) {
                return false;
            }

            $this->checkTimeout($deadline, (float) $timeout);
            usleep(self::POLL);
        }
    }

    /**
     * Calls `$callback` with each new output and error output, until the end of the process.
     *
     * @param callable(string, string): mixed $callback
     *
     * @return int The exit code
     *
     * @throws ProcessTimedOutException The process is still running after `$timeout` (it is not stopped)
     */
    public function watch(callable $callback, ?float $timeout = null): int
    {
        $this->assertStarted();
        $deadline = self::deadline($timeout);

        while (true) {
            $running = $this->isRunning();
            $output = $this->getIncrementalOutput();
            $errorOutput = $this->getIncrementalErrorOutput();

            if ($output !== '' || $errorOutput !== '') {
                $callback($output, $errorOutput);
            }

            if (!$running) {
                return (int) $this->exitCode;
            }

            $this->checkTimeout($deadline, (float) $timeout);
            usleep(self::POLL);
        }
    }

    /**
     * Stops the process: `$signal`, then SIGKILL if it is still running after `$timeout`.
     *
     * @return int|null The exit code, `null` when the process was not started
     */
    public function stop(float $timeout = 10.0, int $signal = self::SIGTERM): ?int
    {
        $process = $this->process;

        if ($process === null) {
            return null;
        }

        if ($this->isRunning()) {
            proc_terminate($process, $signal);
            $deadline = microtime(true) + $timeout;

            while ($this->isRunning() && microtime(true) < $deadline) {
                usleep(self::POLL);
            }

            if ($this->isRunning()) {
                proc_terminate($process, self::SIGKILL);

                while ($this->isRunning()) {
                    usleep(self::POLL);
                }
            }
        }

        return $this->exitCode;
    }

    public function isStarted(): bool
    {
        return $this->process !== null;
    }

    /**
     * @phpstan-impure
     */
    public function isRunning(): bool
    {
        $process = $this->process;

        if ($process === null || $this->exitCode !== null) {
            return false;
        }

        $status = proc_get_status($process);

        if ($status['running']) {
            return true;
        }

        // Given once by proc_get_status(): kept. A process killed by a signal gets 128 + the signal.
        $this->exitCode = $status['signaled'] ? 128 + $status['termsig'] : $status['exitcode'];
        $this->readOutputs();
        proc_close($process);

        return false;
    }

    public function isSuccessful(): bool
    {
        return $this->getExitCode() === 0;
    }

    /**
     * The exit code, `null` while the process runs.
     */
    public function getExitCode(): ?int
    {
        $this->isRunning();

        return $this->exitCode;
    }

    public function getPid(): ?int
    {
        return $this->pid;
    }

    public function getCommandLine(): string
    {
        return is_string($this->command) ? $this->command : implode(' ', array_map(escapeshellarg(...), $this->command));
    }

    public function getOutput(): string
    {
        $this->readOutputs();

        return $this->output;
    }

    public function getErrorOutput(): string
    {
        $this->readOutputs();

        return $this->errorOutput;
    }

    /**
     * The output since the previous call.
     */
    public function getIncrementalOutput(): string
    {
        $output = substr($this->getOutput(), $this->incrementalOutput);
        $this->incrementalOutput += strlen($output);

        return $output;
    }

    /**
     * The error output since the previous call.
     */
    public function getIncrementalErrorOutput(): string
    {
        $output = substr($this->getErrorOutput(), $this->incrementalErrorOutput);
        $this->incrementalErrorOutput += strlen($output);

        return $output;
    }

    private function readOutputs(): void
    {
        if ($this->streams === null) {
            return;
        }

        $this->output .= self::read($this->streams[1], strlen($this->output));
        $this->errorOutput .= self::read($this->streams[2], strlen($this->errorOutput));
    }

    /**
     * Reads what the process wrote after `$offset`. An explicit seek: at the end of the stream, PHP would not
     * read again from the same position (`stream_get_contents()` with an offset skips the seek).
     *
     * @param resource $stream
     */
    private static function read(mixed $stream, int $offset): string
    {
        fseek($stream, $offset);

        return (string) stream_get_contents($stream);
    }

    /**
     * @return array<string, string>|null
     */
    private function environment(): ?array
    {
        if ($this->env === null) {
            return null;
        }

        return array_filter($this->env + getenv(), static fn(?string $value): bool => $value !== null);
    }

    private function assertStarted(): void
    {
        if ($this->process === null) {
            throw new ProcessException('The process "' . $this->getCommandLine() . '" is not started.');
        }
    }

    private function checkTimeout(?float $deadline, float $timeout): void
    {
        if ($deadline !== null && microtime(true) >= $deadline) {
            throw new ProcessTimedOutException($this, $timeout);
        }
    }

    private static function deadline(?float $timeout): ?float
    {
        return $timeout === null ? null : microtime(true) + $timeout;
    }

    private static function temporaryFile(): string
    {
        return tempnam(sys_get_temp_dir(), 'nucleon-process') ?: throw new ProcessException('Cannot create a temporary file.');
    }
}

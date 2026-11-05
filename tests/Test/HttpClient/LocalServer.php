<?php

declare(strict_types=1);

namespace Test\HttpClient;

use Neutrino\Process\Process;
use RuntimeException;

/**
 * Test servers run in processes of their own: `php -S` on fixtures/server.php, the TLS server and the proxy.
 */
final class LocalServer
{
    /** @var array<string, array{Process, int}> */
    private static array $servers = [];

    /**
     * The URL of an HTTP server ("a" and "b" are two servers, so two origins).
     */
    public static function url(string $name = 'a'): string
    {
        $port = self::start($name, static fn(int $port): Process => new Process(
            [PHP_BINARY, '-S', '127.0.0.1:' . $port, __DIR__ . '/fixtures/server.php'],
            env: ['PHP_CLI_SERVER_WORKERS' => '4'],
        ), 'started');

        return 'http://127.0.0.1:' . $port;
    }

    /**
     * The URL of the TLS server, and the file of its self-signed certificate.
     *
     * @return array{string, string}
     */
    public static function tls(): array
    {
        $certificate = sys_get_temp_dir() . '/nucleon-tls-' . getmypid() . '.crt';
        $port = self::start('tls', static fn(int $port): Process => new Process([PHP_BINARY, __DIR__ . '/fixtures/tls-server.php', (string) $port, $certificate]), 'ready');

        return ['https://127.0.0.1:' . $port, $certificate];
    }

    /**
     * The URL of the proxy for https requests (CONNECT).
     */
    public static function connectProxy(): string
    {
        return 'http://127.0.0.1:' . self::start('proxy', static fn(int $port): Process => new Process([PHP_BINARY, __DIR__ . '/fixtures/connect-proxy.php', (string) $port]), 'ready');
    }

    public static function stopAll(): void
    {
        foreach (self::$servers as [$process]) {
            $process->stop(1.0);
        }

        self::$servers = [];
    }

    /**
     * Starts a server once, and waits until it writes `$ready`.
     *
     * @param callable(int): Process $process
     */
    private static function start(string $name, callable $process, string $ready): int
    {
        if (isset(self::$servers[$name])) {
            return self::$servers[$name][1];
        }

        $port = self::freePort();
        $server = $process($port)->start();

        if (!$server->waitUntil(static fn(string $output, string $errorOutput): bool => str_contains($output . $errorOutput, $ready), 10.0)) {
            throw new RuntimeException('The test server "' . $name . '" stopped: ' . $server->getErrorOutput());
        }

        self::$servers[$name] = [$server, $port];

        return $port;
    }

    private static function freePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');

        if ($socket === false) {
            throw new RuntimeException('No free port.');
        }

        $port = (int) substr((string) strrchr((string) stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);

        return $port;
    }
}

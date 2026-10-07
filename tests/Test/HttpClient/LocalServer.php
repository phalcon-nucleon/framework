<?php

declare(strict_types=1);

namespace Test\HttpClient;

use RuntimeException;

/**
 * Test servers run in processes of their own: `php -S` on fixtures/server.php, and the TLS server.
 */
final class LocalServer
{
    /** @var array<string, array{resource, int}> */
    private static array $servers = [];

    /**
     * The URL of an HTTP server ("a" and "b" are two servers, so two origins).
     */
    public static function url(string $name = 'a'): string
    {
        if (!isset(self::$servers[$name])) {
            $port = self::freePort();
            $process = proc_open(
                [PHP_BINARY, '-S', '127.0.0.1:' . $port, __DIR__ . '/fixtures/server.php'],
                [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
                $pipes,
                null,
                ['PHP_CLI_SERVER_WORKERS' => '4'] + getenv(),
            );

            self::$servers[$name] = [self::started($process), $port];
            self::wait($port);
        }

        return 'http://127.0.0.1:' . self::$servers[$name][1];
    }

    /**
     * The URL of the TLS server, and the file of its self-signed certificate.
     *
     * @return array{string, string}
     */
    public static function tls(): array
    {
        $certificate = sys_get_temp_dir() . '/nucleon-tls-' . getmypid() . '.crt';

        if (!isset(self::$servers['tls'])) {
            $port = self::freePort();
            $process = proc_open([PHP_BINARY, __DIR__ . '/fixtures/tls-server.php', (string) $port, $certificate], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

            self::$servers['tls'] = [self::started($process), $port];

            if (trim((string) fgets($pipes[1])) !== 'ready') {
                throw new RuntimeException('The TLS server did not start: ' . stream_get_contents($pipes[2]));
            }
        }

        return ['https://127.0.0.1:' . self::$servers['tls'][1], $certificate];
    }

    /**
     * The URL of the proxy for https requests (CONNECT).
     */
    public static function connectProxy(): string
    {
        if (!isset(self::$servers['proxy'])) {
            $port = self::freePort();
            $process = proc_open([PHP_BINARY, __DIR__ . '/fixtures/connect-proxy.php', (string) $port], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

            self::$servers['proxy'] = [self::started($process), $port];

            if (trim((string) fgets($pipes[1])) !== 'ready') {
                throw new RuntimeException('The proxy did not start: ' . stream_get_contents($pipes[2]));
            }
        }

        return 'http://127.0.0.1:' . self::$servers['proxy'][1];
    }

    public static function stopAll(): void
    {
        foreach (self::$servers as [$process]) {
            proc_terminate($process);
            proc_close($process);
        }

        self::$servers = [];
    }

    /**
     * @param resource|false $process
     *
     * @return resource
     */
    private static function started(mixed $process): mixed
    {
        if (!is_resource($process)) {
            throw new RuntimeException('Cannot start the test server.');
        }

        register_shutdown_function(self::stopAll(...));

        return $process;
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

    private static function wait(int $port): void
    {
        for ($i = 0; $i < 100; $i++) {
            $socket = @fsockopen('127.0.0.1', $port, $errno, $error, 0.1);

            if ($socket !== false) {
                fclose($socket);

                return;
            }

            usleep(50000);
        }

        throw new RuntimeException('The test server did not start on port ' . $port . '.');
    }
}

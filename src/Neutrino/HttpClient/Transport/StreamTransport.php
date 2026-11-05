<?php

declare(strict_types=1);

namespace Neutrino\HttpClient\Transport;

use Generator;
use Neutrino\HttpClient\Exception\TransportException;
use Neutrino\HttpClient\Head;
use Neutrino\HttpClient\Request;
use Neutrino\HttpClient\Transport;

/**
 * Transport on the PHP streams (`http` wrapper), when `ext-curl` is missing. HTTP/1.x only.
 */
final class StreamTransport implements Transport
{
    private const int CHUNK_SIZE = 16384;

    public function exchange(Request $request): Generator
    {
        $scheme = parse_url($request->url, PHP_URL_SCHEME);

        // The client only builds http(s) URLs: a check against another wrapper (file://, phar://…).
        if ($scheme !== 'http' && $scheme !== 'https') {
            throw new TransportException('Unsupported protocol "' . $scheme . '".');
        }

        $start = microtime(true);
        $error = null;
        set_error_handler(static function (int $type, string $message) use (&$error): bool {
            $error = $message;

            return true;
        });

        try {
            $stream = fopen($request->url, 'r', false, stream_context_create($this->context($request)));
        } finally {
            restore_error_handler();
        }

        if ($stream === false) {
            throw new TransportException(preg_replace('/^fopen\([^)]*\): /', '', (string) $error) ?: 'Cannot open "' . $request->url . '".');
        }

        try {
            $meta = stream_get_meta_data($stream);

            yield Head::parse(is_array($meta['wrapper_data']) ? array_filter($meta['wrapper_data'], is_string(...)) : []);

            while (!feof($stream)) {
                $remaining = $request->maxDuration > 0 ? $request->maxDuration - (microtime(true) - $start) : 0.0;

                if ($request->maxDuration > 0 && $remaining <= 0) {
                    throw new TransportException('Max duration reached for "' . $request->url . '".');
                }

                $timeout = self::timeout($request, $remaining);

                if ($timeout > 0) {
                    stream_set_timeout($stream, (int) $timeout, (int) (fmod($timeout, 1) * 1e6));
                }

                $chunk = fread($stream, self::CHUNK_SIZE);

                if (stream_get_meta_data($stream)['timed_out']) {
                    throw new TransportException('Timeout reached for "' . $request->url . '".');
                }

                if ($chunk === false) {
                    throw new TransportException('Cannot read the response of "' . $request->url . '".');
                }

                if ($chunk !== '') {
                    yield $chunk;
                }
            }
        } finally {
            fclose($stream);
        }
    }

    /**
     * The read timeout: the idle timeout, bounded by the remaining duration; -1 when there is none.
     */
    private static function timeout(Request $request, float $remaining): float
    {
        $timeouts = array_filter([$request->timeout, $remaining], static fn(float $timeout): bool => $timeout > 0);

        return $timeouts === [] ? -1.0 : min($timeouts);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function context(Request $request): array
    {
        $headers = $request->headerLines();
        $http = [
            'method'           => $request->method,
            'protocol_version' => $request->httpVersion === '1.0' ? 1.0 : 1.1,
            'ignore_errors'    => true,
            'follow_location'  => 0,
            'timeout'          => self::timeout($request, $request->maxDuration),
        ];

        if (!isset($request->headers['connection'])) {
            // No keep-alive: the stream ends with the response.
            $headers[] = 'connection: close';
        }

        if ($request->body !== '') {
            $http['content'] = $request->body;
        }

        $proxy = $request->proxy();

        if ($proxy !== null) {
            $parts = parse_url(str_contains($proxy, '://') ? $proxy : 'http://' . $proxy);

            if ($parts === false || !isset($parts['host'])) {
                throw new TransportException('Invalid proxy "' . $proxy . '".');
            }

            $http['proxy'] = 'tcp://' . $parts['host'] . ':' . ($parts['port'] ?? 80);
            // An https request goes through a CONNECT tunnel, with a relative URI.
            $http['request_fulluri'] = str_starts_with($request->url, 'http://');

            if (isset($parts['user'])) {
                $headers[] = 'proxy-authorization: Basic ' . base64_encode(urldecode($parts['user']) . ':' . urldecode($parts['pass'] ?? ''));
            }
        }

        $http['header'] = $headers;

        $ssl = [
            'verify_peer'      => $request->verifyPeer,
            'verify_peer_name' => $request->verifyHost,
            'allow_self_signed' => false,
        ];

        if ($request->cafile !== null) {
            $ssl['cafile'] = $request->cafile;
        }

        return ['http' => $http, 'ssl' => $ssl];
    }
}

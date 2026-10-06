<?php

declare(strict_types=1);

namespace Neutrino\HttpClient;

use Closure;
use JsonException;
use Neutrino\HttpClient\Exception\InvalidArgumentException;

/**
 * The options of a request: their default values, their merging and their resolution into a {@see Request}.
 */
final class Options
{
    /**
     * The options and their default values.
     *
     * - `base_uri`: URL the relative URLs are resolved against;
     * - `query`: parameters added to the query string;
     * - `headers`: `['name' => 'value' or list of values]`, or a list of "name: value" lines;
     * - `body`: a string, an array (form encoded), a resource or an iterable of strings (read in memory);
     * - `json`: a value sent as JSON (with `content-type: application/json`);
     * - `auth_basic`: "user:password" or `[user, password]`; `auth_bearer`: a token;
     * - `timeout`: idle timeout in seconds (`default_socket_timeout` when `null`); `max_duration`: maximum
     *   duration of the request in seconds (0: none);
     * - `max_redirects`: redirections followed (0: none);
     * - `proxy`: "http://host:port"; `no_proxy`: hosts, comma separated or a list (`*`: all);
     * - `verify_peer`, `verify_host`, `cafile`: TLS checks;
     * - `http_version`: "1.0", "1.1" or "2.0" (curl only, 1.1 otherwise);
     * - `buffer`: keeps the body read by `chunks()` for `getContent()`;
     * - `on_progress`: `function (int $downloaded, int $total, array $info): void`, called for each chunk received
     *   (`$total`: 0 when unknown);
     * - `user_data`: any value, returned by `getInfo('user_data')`.
     */
    public const array DEFAULTS = [
        'base_uri'      => null,
        'query'         => [],
        'headers'       => [],
        'body'          => '',
        'json'          => null,
        'auth_basic'    => null,
        'auth_bearer'   => null,
        'timeout'       => null,
        'max_duration'  => 0,
        'max_redirects' => 20,
        'proxy'         => null,
        'no_proxy'      => null,
        'verify_peer'   => true,
        'verify_host'   => true,
        'cafile'        => null,
        'http_version'  => null,
        'buffer'        => true,
        'on_progress'   => null,
        'user_data'     => null,
    ];

    private function __construct() {}

    /**
     * Merges options: `headers` and `query` are merged, the other options are replaced.
     *
     * @param array<string, mixed> $options
     * @param array<string, mixed> $with
     *
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException An unknown option
     */
    public static function merge(array $options, array $with): array
    {
        $unknown = array_diff_key($with, self::DEFAULTS);

        if ($unknown !== []) {
            throw new InvalidArgumentException('Unknown HTTP client option "' . array_key_first($unknown) . '": use ' . implode(', ', array_keys(self::DEFAULTS)) . '.');
        }

        if (isset($with['headers'])) {
            $with['headers'] = self::headers($with['headers']) + self::headers($options['headers'] ?? []);
        }

        if (isset($with['query'])) {
            $with['query'] = self::array('query', $with['query']) + self::array('query', $options['query'] ?? []);
        }

        return $with + $options;
    }

    /**
     * Resolves the options of a request.
     *
     * @param array<string, mixed> $options Merged with {@see self::DEFAULTS}
     *
     * @throws InvalidArgumentException
     */
    public static function prepare(string $method, string $url, array $options): Request
    {
        $options += self::DEFAULTS;
        $headers = self::headers($options['headers']);
        $url = self::url($url, $options['base_uri'], self::array('query', $options['query']));

        [$body, $contentType] = self::body($options['body'], $options['json']);

        if ($contentType !== null && !isset($headers['content-type'])) {
            $headers['content-type'] = [$contentType];
        }

        $authorization = self::authorization($options['auth_basic'], $options['auth_bearer']);

        if ($authorization !== null) {
            $headers['authorization'] = [$authorization];
        }

        $proxy = $options['proxy'];
        $noProxy = $options['no_proxy'];
        $cafile = $options['cafile'];

        return new Request(
            strtoupper($method),
            $url,
            $headers,
            $body,
            self::seconds('timeout', $options['timeout'] ?? ini_get('default_socket_timeout')),
            self::seconds('max_duration', $options['max_duration']),
            $proxy === null ? null : self::string('proxy', $proxy),
            (bool) $options['verify_peer'],
            (bool) $options['verify_host'],
            $cafile === null ? null : self::string('cafile', $cafile),
            match ($options['http_version']) {
                null, '1.1' => '1.1',
                '1.0', '2.0' => $options['http_version'],
                default => throw new InvalidArgumentException('Option "http_version": use "1.0", "1.1" or "2.0".'),
            },
            match (true) {
                $noProxy === null => [],
                is_string($noProxy) => explode(',', $noProxy),
                is_array($noProxy) => array_values(array_map(static fn(mixed $host): string => self::string('no_proxy', $host), $noProxy)),
                default => throw new InvalidArgumentException('Option "no_proxy": a string or a list of hosts.'),
            },
        );
    }

    /**
     * The `on_progress` callback.
     *
     * @param array<string, mixed> $options
     *
     * @return (Closure(int, int, array<string, mixed>): mixed)|null
     */
    public static function onProgress(array $options): ?Closure
    {
        $callback = $options['on_progress'] ?? null;

        return match (true) {
            $callback === null => null,
            is_callable($callback) => $callback(...),
            default => throw new InvalidArgumentException('Option "on_progress": a callable.'),
        };
    }

    /**
     * Resolves a URL against another (RFC 3986), without its fragment.
     *
     * @throws InvalidArgumentException A URL other than http(s)
     */
    public static function resolve(string $url, ?string $base = null): string
    {
        $parts = parse_url($url);

        if ($parts === false) {
            throw new InvalidArgumentException('Malformed URL "' . $url . '".');
        }

        if (!isset($parts['scheme']) && $base !== null) {
            $baseParts = parse_url($base);

            if ($baseParts === false || !isset($baseParts['scheme'], $baseParts['host'])) {
                throw new InvalidArgumentException('Option "base_uri": "' . $base . '" is not an absolute URL.');
            }

            if (isset($parts['host'])) {
                $parts['scheme'] = $baseParts['scheme'];
            } else {
                $path = $parts['path'] ?? '';

                if ($path === '') {
                    $path = $baseParts['path'] ?? '/';
                    $parts['query'] ??= $baseParts['query'] ?? null;
                } elseif ($path[0] !== '/') {
                    $basePath = $baseParts['path'] ?? '/';
                    $path = substr($basePath, 0, (int) strrpos($basePath, '/') + 1) . $path;
                }

                $parts = ['path' => self::removeDotSegments($path)] + $parts + array_intersect_key($baseParts, array_flip(['scheme', 'host', 'port', 'user', 'pass']));
            }
        }

        $scheme = strtolower($parts['scheme'] ?? '');

        if (($scheme !== 'http' && $scheme !== 'https') || !isset($parts['host'])) {
            throw new InvalidArgumentException('Unsupported URL "' . $url . '": only absolute http and https URLs are allowed.');
        }

        return $scheme . '://'
            . (isset($parts['user']) ? $parts['user'] . (isset($parts['pass']) ? ':' . $parts['pass'] : '') . '@' : '')
            . $parts['host']
            . (isset($parts['port']) ? ':' . $parts['port'] : '')
            . (($parts['path'] ?? '') === '' ? '/' : $parts['path'])
            . (isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '');
    }

    /**
     * @param array<array-key, mixed> $query
     */
    private static function url(string $url, mixed $base, array $query): string
    {
        $url = self::resolve($url, $base === null ? null : self::string('base_uri', $base));

        if ($query === []) {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @return array<string, list<string>>
     */
    private static function headers(mixed $headers): array
    {
        $normalized = [];

        foreach (self::array('headers', $headers) as $name => $values) {
            if (is_int($name)) {
                [$name, $values] = array_pad(explode(':', self::string('headers', $values), 2), 2, '');
            }

            $name = strtolower(trim((string) $name));

            foreach (is_array($values) ? $values : [$values] as $value) {
                $value = trim(self::string('headers', $value));

                // No header injection.
                if (preg_match('/[\r\n\0]/', $name . $value) === 1) {
                    throw new InvalidArgumentException('Option "headers": "' . $name . '" contains a line break.');
                }

                $normalized[$name][] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @return array{string, string|null} The body and its content type
     */
    private static function body(mixed $body, mixed $json): array
    {
        if ($json !== null) {
            try {
                return [json_encode($json, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION), 'application/json'];
            } catch (JsonException $e) {
                throw new InvalidArgumentException('Option "json": ' . $e->getMessage() . '.', 0, $e);
            }
        }

        return match (true) {
            is_string($body) => [$body, null],
            is_array($body) => [http_build_query($body, '', '&'), 'application/x-www-form-urlencoded'],
            is_resource($body) => [(string) stream_get_contents($body), null],
            is_iterable($body) => [implode('', array_map(static fn(mixed $chunk): string => self::string('body', $chunk), iterator_to_array($body, false))), null],
            default => throw new InvalidArgumentException('Option "body": a string, an array, a resource or an iterable of strings.'),
        };
    }

    private static function authorization(mixed $basic, mixed $bearer): ?string
    {
        if ($basic !== null && $bearer !== null) {
            throw new InvalidArgumentException('Options "auth_basic" and "auth_bearer" cannot be used together.');
        }

        if ($bearer !== null) {
            return 'Bearer ' . self::string('auth_bearer', $bearer);
        }

        if ($basic === null) {
            return null;
        }

        $credentials = is_array($basic) ? implode(':', array_map(static fn(mixed $part): string => self::string('auth_basic', $part), $basic)) : self::string('auth_basic', $basic);

        return 'Basic ' . base64_encode($credentials);
    }

    private static function removeDotSegments(string $path): string
    {
        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '..') {
                array_pop($segments);
            } elseif ($segment !== '.') {
                $segments[] = $segment;
            }
        }

        $resolved = implode('/', $segments);
        $last = basename($path);

        // A path ending with a dot segment designates a directory.
        if ($last === '.' || $last === '..') {
            $resolved .= '/';
        }

        return str_starts_with($resolved, '/') ? $resolved : '/' . $resolved;
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function array(string $option, mixed $value): array
    {
        return is_array($value) ? $value : throw new InvalidArgumentException('Option "' . $option . '": an array.');
    }

    private static function string(string $option, mixed $value): string
    {
        return is_string($value) || is_int($value) || is_float($value) ? (string) $value : throw new InvalidArgumentException('Option "' . $option . '": a string.');
    }

    private static function seconds(string $option, mixed $value): float
    {
        return is_numeric($value) && $value >= 0 ? (float) $value : throw new InvalidArgumentException('Option "' . $option . '": a number of seconds.');
    }
}

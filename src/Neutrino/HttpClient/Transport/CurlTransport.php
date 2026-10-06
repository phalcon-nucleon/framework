<?php

declare(strict_types=1);

namespace Neutrino\HttpClient\Transport;

use CurlHandle;
use Generator;
use Neutrino\HttpClient\Exception\TransportException;
use Neutrino\HttpClient\Head;
use Neutrino\HttpClient\Request;
use Neutrino\HttpClient\Transport;

/**
 * Transport on `ext-curl`. The body is received as it comes (`curl_multi`), the redirections are left to the client.
 */
final class CurlTransport implements Transport
{
    private const array HTTP_VERSIONS = [
        '1.0' => CURL_HTTP_VERSION_1_0,
        '1.1' => CURL_HTTP_VERSION_1_1,
        '2.0' => CURL_HTTP_VERSION_2_0,
    ];

    public function exchange(Request $request): Generator
    {
        $handle = curl_init();
        $multi = curl_multi_init();
        $headerLines = [];
        $headComplete = false;
        $body = '';

        curl_setopt_array($handle, $this->options($request) + [
            CURLOPT_HEADERFUNCTION => static function (CurlHandle $handle, string $line) use (&$headerLines, &$headComplete): int {
                if (rtrim($line, "\r\n") === '') {
                    // The end of a head: an interim response (1xx) is followed by another.
                    $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
                    $headComplete = $status >= 200 || $status === 0;
                } else {
                    $headerLines[] = $line;
                }

                return strlen($line);
            },
            CURLOPT_WRITEFUNCTION => static function (CurlHandle $handle, string $data) use (&$body): int {
                $body .= $data;

                return strlen($data);
            },
        ]);

        curl_multi_add_handle($multi, $handle);

        try {
            $headSent = false;

            do {
                $status = curl_multi_exec($multi, $running);

                if ($status !== CURLM_OK) {
                    throw new TransportException(curl_multi_strerror($status) ?? 'cURL error.');
                }

                if (!$headSent && $headComplete) {
                    $headSent = true;

                    yield Head::parse($headerLines);
                }

                if ($headSent && $body !== '') {
                    $chunk = $body;
                    $body = '';

                    yield $chunk;
                }

                if ($running > 0 && curl_multi_select($multi, 0.5) === -1) {
                    usleep(1000);
                }
            } while ($running > 0);

            $info = curl_multi_info_read($multi);
            $result = is_array($info) && is_int($info['result']) ? $info['result'] : CURLE_OK;

            if ($result !== CURLE_OK) {
                throw new TransportException(curl_error($handle) ?: curl_strerror($result) ?? 'cURL error.');
            }

            if (!$headSent) {
                yield Head::parse($headerLines);
            }

            if ($body !== '') {
                yield $body;
            }
        } finally {
            curl_multi_remove_handle($multi, $handle);
            curl_multi_close($multi); // the handle is freed with its last reference (curl_close() is deprecated in PHP 8.5)
        }
    }

    /**
     * @return array<int, mixed>
     */
    private function options(Request $request): array
    {
        $options = [
            CURLOPT_URL            => $request->url,
            CURLOPT_HTTPHEADER     => [...$request->headerLines(), 'expect:'],
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTP_VERSION   => self::HTTP_VERSIONS[$request->httpVersion] ?? CURL_HTTP_VERSION_1_1,
            CURLOPT_SSL_VERIFYPEER => $request->verifyPeer,
            CURLOPT_SSL_VERIFYHOST => $request->verifyHost ? 2 : 0,
            CURLOPT_CONNECTTIMEOUT_MS => (int) ceil($request->timeout * 1000),
            // Idle timeout: less than one byte per second during `timeout`.
            CURLOPT_LOW_SPEED_LIMIT => 1,
            CURLOPT_LOW_SPEED_TIME => max(1, (int) ceil($request->timeout)),
            CURLOPT_TIMEOUT_MS     => (int) ceil($request->maxDuration * 1000),
            CURLOPT_PROXY          => $request->proxy() ?? '',
            CURLOPT_NOPROXY        => '',
            CURLOPT_NOSIGNAL       => true,
        ];

        if ($request->method === 'HEAD') {
            $options[CURLOPT_NOBODY] = true;
        } else {
            $options[CURLOPT_CUSTOMREQUEST] = $request->method;
        }

        if ($request->body !== '') {
            $options[CURLOPT_POSTFIELDS] = $request->body;
        }

        if ($request->cafile !== null) {
            $options[CURLOPT_CAINFO] = $request->cafile;
        }

        return $options;
    }
}

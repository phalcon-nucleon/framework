<?php

declare(strict_types=1);

namespace Neutrino\HttpClient;

use Closure;
use Generator;
use JsonException;
use Neutrino\HttpClient\Exception\ClientException;
use Neutrino\HttpClient\Exception\DecodingException;
use Neutrino\HttpClient\Exception\InvalidArgumentException;
use Neutrino\HttpClient\Exception\RedirectionException;
use Neutrino\HttpClient\Exception\ServerException;
use Neutrino\HttpClient\Exception\TransportException;
use Throwable;

/**
 * A response: the request is sent on the first read of the response (or when it is destroyed unread), and the
 * redirections are followed.
 */
final class Response implements ResponseInterface
{
    /**
     * Headers not sent to another host on a redirection.
     */
    private const array SENSITIVE_HEADERS = ['authorization', 'cookie', 'proxy-authorization'];

    private ?Head $head = null;

    /** @var Generator<int, Head|string>|null The body being received */
    private ?Generator $exchange = null;

    private ?string $content = null;

    private int $httpCode = 0;

    private string $httpMethod;

    private string $url;

    private int $redirectCount = 0;

    private ?string $redirectUrl = null;

    private float $startTime = 0.0;

    private float $totalTime = 0.0;

    private int $sizeDownload = 0;

    private bool $sent = false;

    /** Time limit of the request (`max_duration`), as a timestamp */
    private ?float $deadline = null;

    /**
     * @param (Closure(int, int, array<string, mixed>): mixed)|null $onProgress
     */
    public function __construct(
        private readonly Transport $transport,
        private Request $request,
        private readonly int $maxRedirects = 20,
        private readonly bool $buffer = true,
        private readonly ?Closure $onProgress = null,
        private readonly mixed $userData = null,
    ) {
        $this->httpMethod = $request->method;
        $this->url = $request->url;
    }

    /**
     * Completes a request never read: it is sent anyway, its errors are ignored.
     */
    public function __destruct()
    {
        if ($this->sent) {
            $this->exchange = null;

            return;
        }

        try {
            $this->getContent(false);
        } catch (Throwable) {
            // Nobody reads the response.
        }
    }

    public function getStatusCode(): int
    {
        return $this->head()->status;
    }

    public function getHeaders(bool $throw = true): array
    {
        $head = $this->head();

        if ($throw) {
            $this->check();
        }

        return $head->headers;
    }

    public function getContent(bool $throw = true): string
    {
        $this->head();

        if ($throw) {
            $this->check();
        }

        if ($this->content === null) {
            if ($this->exchange === null) {
                throw new TransportException('The body of "' . $this->url . '" was already read without buffer.');
            }

            $content = '';

            foreach ($this->receive() as $chunk) {
                $content .= $chunk;
            }

            $this->content = $content;
        }

        return $this->content;
    }

    public function toArray(bool $throw = true): array
    {
        $content = $this->getContent($throw);

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        } catch (JsonException $e) {
            throw new DecodingException('Invalid JSON returned for "' . $this->url . '": ' . $e->getMessage() . '.', 0, $e);
        }

        return is_array($data) ? $data : throw new DecodingException('JSON returned for "' . $this->url . '" is not an array or an object.');
    }

    public function chunks(bool $throw = true): Generator
    {
        $this->head();

        if ($throw) {
            $this->check();
        }

        if ($this->content !== null) {
            if ($this->content !== '') {
                yield $this->content;
            }

            return;
        }

        if ($this->exchange === null) {
            throw new TransportException('The body of "' . $this->url . '" was already read without buffer.');
        }

        $content = $this->buffer ? '' : null;

        foreach ($this->receive() as $chunk) {
            if ($content !== null) {
                $content .= $chunk;
            }

            yield $chunk;
        }

        $this->content = $content;
    }

    public function getInfo(?string $type = null): mixed
    {
        $info = $this->info();

        return $type === null ? $info : ($info[$type] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    private function info(): array
    {
        return [
            'http_code'      => $this->httpCode,
            'http_method'    => $this->httpMethod,
            'url'            => $this->url,
            'redirect_count' => $this->redirectCount,
            'redirect_url'   => $this->redirectUrl,
            'start_time'     => $this->startTime,
            'total_time'     => $this->totalTime,
            'size_download'  => $this->sizeDownload,
            'user_data'      => $this->userData,
        ];
    }

    /**
     * Sends the request and follows the redirections, up to the head of the final response.
     */
    private function head(): Head
    {
        if ($this->head !== null) {
            return $this->head;
        }

        if ($this->sent) {
            throw new TransportException('The request to "' . $this->url . '" failed.');
        }

        $this->sent = true;
        $this->startTime = $start = microtime(true);
        $this->deadline = $this->request->maxDuration > 0 ? $start + $this->request->maxDuration : null;

        while (true) {
            $this->exchange = $this->transport->exchange($this->request);
            $head = $this->exchange->current();

            if (!$head instanceof Head) {
                throw new TransportException('The transport did not return the head of the response.');
            }

            $this->httpCode = $head->status;
            $this->url = $this->request->url;
            $this->totalTime = microtime(true) - $start;

            $location = $head->status >= 300 && $head->status < 400 ? $head->header('location') : null;

            if ($location === null) {
                return $this->head = $head;
            }

            try {
                $this->redirectUrl = $next = Options::resolve($location, $this->request->url);
            } catch (InvalidArgumentException $e) {
                // file://, gopher://… sent by the server.
                throw new TransportException('Redirection of "' . $this->url . '" refused: ' . $e->getMessage(), 0, $e);
            }

            if ($this->redirectCount >= $this->maxRedirects) {
                return $this->head = $head;
            }

            $this->exchange = null; // aborts the exchange
            $this->redirectCount++;
            $this->request = $this->redirect($head->status, $next);
            $this->redirectUrl = null;
            $this->httpMethod = $this->request->method;
        }
    }

    /**
     * The request to the location of a redirection.
     */
    private function redirect(int $status, string $location): Request
    {
        $request = $this->request;
        $headers = $request->headers;
        $changes = ['url' => $location];

        if (self::origin($location) !== self::origin($request->url)) {
            $headers = array_diff_key($headers, array_flip(self::SENSITIVE_HEADERS));
        }

        // 301, 302 and 303 turn a request with a body into a GET; 307 and 308 keep the method and the body.
        if (($status === 303 && $request->method !== 'HEAD') || (($status === 301 || $status === 302) && $request->method === 'POST')) {
            $changes['method'] = 'GET';
            $changes['body'] = '';
            unset($headers['content-type'], $headers['content-length']);
        }

        if ($this->deadline !== null) {
            $changes['maxDuration'] = $this->deadline - microtime(true);

            if ($changes['maxDuration'] <= 0) {
                throw new TransportException('Max duration reached for "' . $location . '".');
            }
        }

        return $request->with(['headers' => $headers] + $changes);
    }

    /**
     * Receives the rest of the body.
     *
     * @return Generator<int, string>
     */
    private function receive(): Generator
    {
        $exchange = $this->exchange;
        $this->exchange = null;

        if ($exchange === null) {
            return;
        }

        $total = (int) ($this->head?->header('content-length') ?? 0);
        $exchange->next(); // after the head

        for (; $exchange->valid(); $exchange->next()) {
            $chunk = $exchange->current();

            if (!is_string($chunk) || $chunk === '') {
                continue;
            }

            $this->sizeDownload += strlen($chunk);
            $this->totalTime = microtime(true) - $this->startTime;

            if ($this->onProgress !== null) {
                ($this->onProgress)($this->sizeDownload, $total, $this->info());
            }

            yield $chunk;

            if ($this->deadline !== null && microtime(true) > $this->deadline) {
                throw new TransportException('Max duration reached for "' . $this->url . '".');
            }
        }

        $this->totalTime = microtime(true) - $this->startTime;
    }

    /**
     * @throws RedirectionException|ClientException|ServerException
     */
    private function check(): void
    {
        $status = $this->head()->status;

        match (true) {
            $status >= 500 => throw new ServerException($this),
            $status >= 400 => throw new ClientException($this),
            $status >= 300 => throw new RedirectionException($this),
            default => null,
        };
    }

    private static function origin(string $url): string
    {
        $parts = (array) parse_url($url);

        return strtolower(($parts['scheme'] ?? '') . '://' . ($parts['host'] ?? '') . ':' . ($parts['port'] ?? ''));
    }
}

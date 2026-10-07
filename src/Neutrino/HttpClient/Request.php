<?php

declare(strict_types=1);

namespace Neutrino\HttpClient;

/**
 * A request ready to be sent: the options are resolved (see {@see Options::prepare()}).
 */
final readonly class Request
{
    /**
     * @param array<string, list<string>> $headers By lower-case name
     * @param float                       $timeout Idle timeout, in seconds (0: none)
     * @param float                       $maxDuration Maximum duration of the exchange, in seconds (0: none)
     * @param string|null                 $proxy   The configured proxy (see {@see self::proxy()})
     * @param list<string>                $noProxy Hosts reached without the proxy (`*` for all)
     */
    public function __construct(
        public string $method,
        public string $url,
        public array $headers = [],
        public string $body = '',
        public float $timeout = 60.0,
        public float $maxDuration = 0.0,
        public ?string $proxy = null,
        public bool $verifyPeer = true,
        public bool $verifyHost = true,
        public ?string $cafile = null,
        public string $httpVersion = '1.1',
        public array $noProxy = [],
    ) {}

    /**
     * The proxy of the URL: none when its host matches `no_proxy` (the host or one of its subdomains).
     */
    public function proxy(): ?string
    {
        $host = strtolower((string) parse_url($this->url, PHP_URL_HOST));

        foreach ($this->noProxy as $rule) {
            $rule = strtolower(ltrim(trim($rule), '.'));

            if ($rule === '*' || $host === $rule || str_ends_with($host, '.' . $rule)) {
                return null;
            }
        }

        return $this->proxy;
    }

    /**
     * The headers as lines ("name: value").
     *
     * @return list<string>
     */
    public function headerLines(): array
    {
        $lines = [];

        foreach ($this->headers as $name => $values) {
            foreach ($values as $value) {
                $lines[] = $name . ': ' . $value;
            }
        }

        return $lines;
    }

    /**
     * A copy with other values (for a redirection or the remaining duration).
     *
     * @param array{method?: string, url?: string, headers?: array<string, list<string>>, body?: string, maxDuration?: float} $changes
     */
    public function with(array $changes): self
    {
        return new self(
            $changes['method'] ?? $this->method,
            $changes['url'] ?? $this->url,
            $changes['headers'] ?? $this->headers,
            $changes['body'] ?? $this->body,
            $this->timeout,
            $changes['maxDuration'] ?? $this->maxDuration,
            $this->proxy,
            $this->verifyPeer,
            $this->verifyHost,
            $this->cafile,
            $this->httpVersion,
            $this->noProxy,
        );
    }
}

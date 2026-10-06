<?php

declare(strict_types=1);

namespace Neutrino\HttpClient;

use Neutrino\HttpClient\Exception\TransportException;

/**
 * The status and the headers of a response.
 */
final readonly class Head
{
    /**
     * @param array<string, list<string>> $headers By lower-case name
     */
    public function __construct(public int $status, public array $headers = []) {}

    /**
     * From the raw lines of the last response of the exchange (after a "100 Continue").
     *
     * @param iterable<string> $lines
     */
    public static function parse(iterable $lines): self
    {
        $status = null;
        $headers = [];

        foreach ($lines as $line) {
            $line = rtrim($line, "\r\n");

            if (preg_match('#^HTTP/[\d.]+ (\d{3})#', $line, $match) === 1) {
                $status = (int) $match[1];
                $headers = [];
            } elseif (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($name))][] = trim($value);
            }
        }

        return new self($status ?? throw new TransportException('Invalid HTTP response: no status line.'), $headers);
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)][0] ?? null;
    }
}

<?php

declare(strict_types=1);

namespace Test\HttpClient;

use Neutrino\HttpClient\Exception\TransportException;
use Neutrino\HttpClient\Head;
use PHPUnit\Framework\TestCase;

final class HeadTest extends TestCase
{
    public function testParse(): void
    {
        $head = Head::parse([
            "HTTP/1.1 100 Continue\r\n",
            "HTTP/1.1 201 Created\r\n",
            "Content-Type: application/json\r\n",
            "Set-Cookie: a=1\r\n",
            "set-cookie: b=2\r\n",
            "X-Empty:\r\n",
        ]);

        $this->assertSame(201, $head->status);
        $this->assertSame(['content-type' => ['application/json'], 'set-cookie' => ['a=1', 'b=2'], 'x-empty' => ['']], $head->headers);
        $this->assertSame('application/json', $head->header('Content-Type'));
        $this->assertNull($head->header('location'));
    }

    public function testNoStatusLine(): void
    {
        $this->expectException(TransportException::class);

        Head::parse(['Content-Type: text/plain']);
    }
}

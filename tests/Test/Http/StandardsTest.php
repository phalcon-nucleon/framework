<?php

declare(strict_types=1);

namespace Test\Http;

use Neutrino\Http\Standards\Method;
use Neutrino\Http\Standards\StatusCode;
use Phalcon\Http\Response;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class StandardsTest extends TestCase
{
    public function testEveryStatusCodeHasAMessage(): void
    {
        $codes = array_filter((new ReflectionClass(StatusCode::class))->getConstants(), 'is_int');

        foreach ($codes as $name => $code) {
            $this->assertIsString(StatusCode::message($code), $name);
        }
        $this->assertNull(StatusCode::message(299));
    }

    public function testMessagesMatchPhalconExceptKnownDifferences(): void
    {
        // Phalcon's table keeps obsolete phrases for these codes; Nucleon follows the IANA registry.
        $differences = [
            207 => 'Multi-status',
            408 => 'Request Time-out',
            414 => 'Request-URI Too Large',
            416 => 'Requested range not satisfiable',
            425 => 'Unordered Collection',
            504 => 'Gateway Time-out',
            505 => 'HTTP Version not supported',
        ];

        $actual = [];
        foreach (StatusCode::MESSAGES as $code => $message) {
            $response = new Response();
            $response->setStatusCode($code);

            if ($response->getReasonPhrase() !== $message) {
                $actual[$code] = $response->getReasonPhrase();
            }
        }

        $this->assertSame($differences, $actual);
    }

    public function testDeprecatedNames(): void
    {
        $this->assertSame(StatusCode::UNAUTHORIZED, StatusCode::BAD_UNAUTHORIZED);
        $this->assertSame(StatusCode::UPGRADE_REQUIRED, StatusCode::UPDATE_REQUIRED);
        $this->assertSame(StatusCode::BANDWIDTH_LIMIT_EXCEEDED, StatusCode::BANDWIDTH_LIMIT_EXCEED);
    }

    public function testMethods(): void
    {
        $this->assertSame(
            ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS', 'TRACE', 'CONNECT'],
            array_values((new ReflectionClass(Method::class))->getConstants()),
        );
    }
}

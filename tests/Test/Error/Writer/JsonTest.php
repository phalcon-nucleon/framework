<?php

declare(strict_types=1);

namespace Test\Error\Writer;

use Neutrino\Constants\Services;
use Neutrino\Error\Error;
use Neutrino\Error\Writer\Json;
use Test\TestCase\TestCase;

final class JsonTest extends TestCase
{
    public function testFatalErrorAnsweredWith500(): void
    {
        $response = $this->mockService(Services::RESPONSE, new StubResponse());
        $error = Error::fromError(E_ERROR, 'fatal', __FILE__, __LINE__);

        (new Json())->handle($error);

        $this->assertTrue($response->wasSent);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('application/json; charset=UTF-8', $response->getHeaders()->get('Content-Type'));
        $this->assertSame(
            ['code' => 500, 'status' => 'Internal Server Error', 'debug' => json_decode((string) json_encode($error), true)],
            json_decode((string) $response->getContent(), true),
        );
    }

    public function testResponseAlreadySent(): void
    {
        $response = $this->mockService(Services::RESPONSE, new StubResponse());
        $response->wasSent = true;

        $this->expectOutputRegex('/^\{"code":500,"status":"Internal Server Error","debug":\{"type":-1,/');

        (new Json())->handle(Error::fromException(new \RuntimeException('boom')));

        $this->assertEmpty($response->getContent());
    }

    public function testNonFatalErrorIgnored(): void
    {
        $response = $this->mockService(Services::RESPONSE, new StubResponse());

        $this->expectOutputString('');

        foreach ([E_WARNING, E_NOTICE, E_DEPRECATED, E_USER_ERROR] as $type) {
            (new Json())->handle(Error::fromError($type, 'msg'));
        }

        $this->assertFalse($response->wasSent);
    }
}

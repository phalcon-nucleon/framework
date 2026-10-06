<?php

declare(strict_types=1);

namespace Test\Http;

use Fake\Kernels\Http\Controllers;
use Neutrino\Constants\Services;
use Neutrino\Http\Middleware\Csrf;
use Neutrino\Http\Standards\StatusCode;
use Phalcon\Encryption\Security;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase\ArraySession;
use Test\TestCase\TestCase;

final class CsrfTest extends TestCase
{
    private Security $security;

    protected function setUp(): void
    {
        parent::setUp();

        $di = $this->getDI();
        $di->remove(Services::SESSION);
        $di->setShared(Services::SESSION, new ArraySession());
        $this->security = $di->getShared(Services::SECURITY);

        $this->app->useImplicitView(false);
        $this->app->router->add('/form', [
            'namespace'  => Controllers::class,
            'controller' => 'Stub',
            'action'     => 'index',
            'middleware' => Csrf::class,
        ]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unsafeMethods(): iterable
    {
        yield 'POST' => ['POST'];
        yield 'PUT' => ['PUT'];
        yield 'PATCH' => ['PATCH'];
        yield 'DELETE' => ['DELETE'];
    }

    #[DataProvider('unsafeMethods')]
    public function testTokenInTheHeader(string $method): void
    {
        $this->dispatch('/form', $method, [], [Csrf::HEADER => (string) $this->security->getToken()]);

        $this->assertAllowed();
    }

    #[DataProvider('unsafeMethods')]
    public function testMissingToken(string $method): void
    {
        $this->security->getToken();

        $this->dispatch('/form', $method);

        $this->assertResponseCode(StatusCode::FORBIDDEN);
    }

    #[DataProvider('unsafeMethods')]
    public function testWrongToken(string $method): void
    {
        $this->security->getToken();

        $this->dispatch('/form', $method, [Csrf::FIELD => 'wrong'], [Csrf::HEADER => 'wrong']);

        $this->assertResponseCode(StatusCode::FORBIDDEN);
    }

    public function testTokenInTheBody(): void
    {
        $this->dispatch('/form', 'POST', [Csrf::FIELD => (string) $this->security->getToken()]);

        $this->assertAllowed();
    }

    public function testTokenInAJsonBody(): void
    {
        $this->dispatch('/form', 'POST', [], [], [Csrf::FIELD => (string) $this->security->getToken()]);

        $this->assertAllowed();
    }

    public function testNoSessionToken(): void
    {
        $this->dispatch('/form', 'POST', [Csrf::FIELD => ''], [Csrf::HEADER => '']);

        $this->assertResponseCode(StatusCode::FORBIDDEN);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function safeMethods(): iterable
    {
        yield 'GET' => ['GET'];
        yield 'HEAD' => ['HEAD'];
        yield 'OPTIONS' => ['OPTIONS'];
    }

    #[DataProvider('safeMethods')]
    public function testSafeMethodsAreNotChecked(string $method): void
    {
        $this->dispatch('/form', $method);

        $this->assertAllowed();
        $this->assertFalse($this->getDI()->getService(Services::SESSION)->isResolved() && $this->security->getSessionToken() !== null, 'No token is created.');
    }

    public function testTokenStaysValidForSuccessiveRequests(): void
    {
        $token = (string) $this->security->getToken();

        for ($i = 0; $i < 3; $i++) {
            $this->dispatch('/form', 'POST', [], [Csrf::HEADER => $token]);
            $this->assertAllowed();
        }
    }

    public function testTheTokenOfTheSessionSurvivesTheNextRequests(): void
    {
        $di = $this->getDI();
        $first = Csrf::token($di);

        // Next request: a new security service on the same session, rendering another form.
        $di->remove(Services::SECURITY);
        $di->setShared(Services::SECURITY, new Security());

        $this->assertSame($first, Csrf::token($di));

        // The form of the first tab is still accepted.
        $this->dispatch('/form', 'POST', [Csrf::FIELD => $first]);
        $this->assertAllowed();
    }

    public function testGetTokenOfPhalconReplacesTheTokenOfTheSession(): void
    {
        $di = $this->getDI();
        $first = Csrf::token($di);

        $di->remove(Services::SECURITY);
        $di->setShared(Services::SECURITY, new Security());

        $this->assertNotSame($first, $di->getShared(Services::SECURITY)->getToken(), 'Why Csrf::token() exists.');
    }

    public function testRotation(): void
    {
        $this->getDI()->getShared(Services::CONFIG)->merge(['security' => ['csrf' => ['rotate' => true]]]);
        $token = (string) $this->security->getToken();

        $this->dispatch('/form', 'POST', [], [Csrf::HEADER => $token]);
        $this->assertAllowed();

        $this->resetResponse();
        $this->dispatch('/form', 'POST', [], [Csrf::HEADER => $token]);
        $this->assertResponseCode(StatusCode::FORBIDDEN);
    }

    /**
     * The action ran: no status was set by the middleware.
     */
    private function assertAllowed(): void
    {
        $this->assertNull($this->getDI()->getShared(Services::RESPONSE)->getStatusCode());
    }

    private function resetResponse(): void
    {
        $this->getDI()->remove(Services::RESPONSE);
        $this->getDI()->setShared(Services::RESPONSE, new \Phalcon\Http\Response());
    }
}

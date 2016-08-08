<?php
namespace Middleware;

use Phalcon\Http\Response\StatusCode;
use TestCase\TestCase;

/**
 * Trait ThrottleTest
 *
 * @package Middleware
 */
class ThrottleTest extends TestCase
{

    public function testThrottle()
    {
        $this->app->useImplicitView(false);

        $this->app->router->addGet('/', [
            'namespace'  => 'Stub',
            'controller' => 'StubThrottled',
            'action'     => 'index'
        ]);

        $this->setExpectedExceptionRegExp(\Exception::class);

        for ($i = 0; $i < 11; $i++) {
            // WHEN
             $response = $this->app->handle('/');
        }

        $this->assertEquals(
            StatusCode::TOO_MANY_REQUESTS,
            $this->app->response->getStatusCode()
        );
        $this->assertEquals(
            StatusCode::message(StatusCode::TOO_MANY_REQUESTS),
            $this->app->response->getContent()
        );
        $this->assertEquals(10, $this->app->response->getHeaders()->get('X-RateLimit-Limit'));
        $this->assertEquals(0, $this->app->response->getHeaders()->get('X-RateLimit-Remaining'));
        $this->assertEquals(60, $this->app->response->getHeaders()->get('Retry-After'));
    }
}


<?php
namespace Test\Middleware;

use Fake\Kernels\Http\Controllers\StubController;
use Neutrino\Constants\Services;
use Neutrino\Exceptions\ThrottledException;
use Neutrino\Http\Middleware\ThrottleRequest;
use Neutrino\Http\Standards\StatusCode;
use Neutrino\Middleware\Throttle;
use Phalcon\Http\Response;
use Test\TestCase\TestCase;
use Test\TestCase\UseCaches;

/**
 * Trait ThrottleTest
 *
 * @package Middleware
 */
class ThrottleTest extends TestCase
{
    use UseCaches;

    public function testThrottle()
    {
        $this->app->useImplicitView(false);

        $this->app->router->addGet('/', [
            'namespace'  => \Fake\Kernels\Http\Controllers::class,
            'controller' => 'Stubthrottled',
            'action'     => 'index'
        ]);

        $msg    = StatusCode::message(StatusCode::TOO_MANY_REQUESTS);
        if(\Phalcon\Version::getPart(\Phalcon\Version::VERSION_MEDIUM) >= 2){
            $status = StatusCode::TOO_MANY_REQUESTS;
        } else {
            $status = StatusCode::TOO_MANY_REQUESTS . ' ' . $msg;
        }
        for ($i = 1; $i <= 11; $i++) {
            // WHEN
            if ($i <= 10) {
                $this->dispatch('/');
                $response = $this->app->response;
                $headers  = $response->getHeaders();
                $this->assertNotEquals($status, $response->getStatusCode(), "status:$i");
                $this->assertNotEquals($msg, $response->getContent(), "content:$i");
                $this->assertEquals(10, $headers->get('X-RateLimit-Limit'), "X-RateLimit-Limit:$i");
                $this->assertEquals(10 - $i, $headers->get('X-RateLimit-Remaining'), "X-RateLimit-Remaining:$i");
                $this->assertEquals(null, $headers->get('Retry-After'), "Retry-After:$i");
            } else {
                try {
                    $throttled = null;
                    $this->dispatch('/');
                } catch (ThrottledException $throttled) {
                }

                $this->assertInstanceOf(ThrottledException::class, $throttled);

                $response = $throttled->createResponse();
                $headers  = $response->getHeaders();

                $this->assertEquals($status, $response->getStatusCode(), "status:$i");
                $this->assertEquals($msg, $response->getContent(), "content:$i");
                $this->assertEquals(10, $headers->get('X-RateLimit-Limit'), "X-RateLimit-Limit:$i");
                $this->assertEquals(0, $headers->get('X-RateLimit-Remaining'), "X-RateLimit-Remaining:$i");
                $this->assertEquals(60, $headers->get('Retry-After'), "Retry-After:$i");
            }
        }

        sleep(1);
        try {
            $throttled = null;
            $this->dispatch('/');
        } catch (ThrottledException $throttled) {
        }

        $this->assertInstanceOf(ThrottledException::class, $throttled);

        $response = $throttled->createResponse();

        $this->assertEquals($status, $response->getStatusCode());
        $this->assertEquals($msg, $response->getContent());
        $this->assertEquals(10, $response->getHeaders()->get('X-RateLimit-Limit'));
        $this->assertEquals(0, $response->getHeaders()->get('X-RateLimit-Remaining'));
        $this->assertLessThanOrEqual(59, $response->getHeaders()->get('Retry-After'));
    }

    public function testThrottleFiltered()
    {
        $this->app->useImplicitView(false);

        $this->app->router->addGet('/', [
            'namespace'  => \Fake\Kernels\Http\Controllers::class,
            'controller' => 'Stubthrottled',
            'action'     => 'index'
        ]);

        $this->app->router->addGet('/throttled', [
            'namespace'  => \Fake\Kernels\Http\Controllers::class,
            'controller' => 'Stubthrottled',
            'action'     => 'throttled'
        ]);

        $msg    = StatusCode::message(StatusCode::TOO_MANY_REQUESTS);
        if(\Phalcon\Version::getPart(\Phalcon\Version::VERSION_MEDIUM) >= 2){
            $status = StatusCode::TOO_MANY_REQUESTS;
        } else {
            $status = StatusCode::TOO_MANY_REQUESTS . ' ' . $msg;
        }
        for ($i = 1; $i <= 10; $i++) {
            // WHEN
            $this->dispatch('/');
            $response = $this->app->getDI()->getShared(Services::RESPONSE);

            $this->assertNotEquals($status, $response->getStatusCode());
            $this->assertNotEquals($msg, $response->getContent());
            $this->assertEquals(10, $response->getHeaders()->get('X-RateLimit-Limit'));
            $this->assertEquals(10 - $i, $response->getHeaders()->get('X-RateLimit-Remaining'));
            $this->assertEquals(null, $response->getHeaders()->get('Retry-After'));
        }

        usleep(1000000);

        $this->app->getDI()->remove(Services::RESPONSE);
        $this->app->getDI()->setShared(Services::RESPONSE, function () {
            $response = new Response();
            $response->setHeaders(new Response\Headers());

            return $response;
        });

        $this->dispatch('/throttled');

        $response = $this->app->getDI()->getShared(Services::RESPONSE);

        if(\Phalcon\Version::getPart(\Phalcon\Version::VERSION_MEDIUM) >= 2){
            $this->assertEquals(200, $response->getStatusCode());
        } else {
            $this->assertEquals('200 OK', $response->getStatusCode());
        }
        $this->assertEquals('', $response->getContent());
        $this->assertEquals(false, $response->getHeaders()->get('X-RateLimit-Limit'));
        $this->assertEquals(false, $response->getHeaders()->get('X-RateLimit-Remaining'));
        $this->assertEquals(false, $response->getHeaders()->get('Retry-After'));

        $this->app->getDI()->remove(Services::RESPONSE);
        $this->app->getDI()->setShared(Services::RESPONSE, function () {
            $response = new Response();
            $response->setHeaders(new Response\Headers());

            return $response;
        });

        try {
            $throttled = null;
            $this->dispatch('/');
        } catch (ThrottledException $throttled) {
        }

        $this->assertInstanceOf(ThrottledException::class, $throttled);

        $response = $throttled->createResponse();

        $this->assertEquals($status, $response->getStatusCode());
        $this->assertEquals($msg, $response->getContent());
        $this->assertEquals(10, $response->getHeaders()->get('X-RateLimit-Limit'));
        $this->assertEquals(0, $response->getHeaders()->get('X-RateLimit-Remaining'));
        $this->assertEquals(60, $response->getHeaders()->get('Retry-After'));
    }

    public function testThrottleRegisterFromRoute()
    {
        $this->app->useImplicitView(false);

        StubController::$middlewares = [];

        $this->app->router->addGet('/route-throttled', [
            'namespace'  => \Fake\Kernels\Http\Controllers::class,
            'controller' => 'Stub',
            'action'     => 'index',
            'middleware' => [ThrottleRequest::class => [10, 60]]
        ]);

        $msg    = StatusCode::message(StatusCode::TOO_MANY_REQUESTS);
        if(\Phalcon\Version::getPart(\Phalcon\Version::VERSION_MEDIUM) >= 2){
            $status = StatusCode::TOO_MANY_REQUESTS;
        } else {
            $status = StatusCode::TOO_MANY_REQUESTS . ' ' . $msg;
        }
        for ($i = 1; $i <= 11; $i++) {
            // WHEN
            if ($i <= 10) {
                $this->dispatch('/route-throttled');

                $response = $this->app->response;
                $headers  = $response->getHeaders();
                $this->assertNotEquals($status, $response->getStatusCode(), "status:$i");
                $this->assertNotEquals($msg, $response->getContent(), "content:$i");
                $this->assertEquals(10, $headers->get('X-RateLimit-Limit'), "X-RateLimit-Limit:$i");
                $this->assertEquals(10 - $i, $headers->get('X-RateLimit-Remaining'), "X-RateLimit-Remaining:$i");
                $this->assertEquals(null, $headers->get('Retry-After'), "Retry-After:$i");
            } else {
                try {
                    $throttled = null;
                    $this->dispatch('/route-throttled');
                } catch (ThrottledException $throttled) {
                }

                $this->assertInstanceOf(ThrottledException::class, $throttled);

                $response = $throttled->createResponse();
                $headers  = $response->getHeaders();

                $this->assertEquals($status, $response->getStatusCode(), "status:$i");
                $this->assertEquals($msg, $response->getContent(), "content:$i");
                $this->assertEquals(10, $headers->get('X-RateLimit-Limit'), "X-RateLimit-Limit:$i");
                $this->assertEquals(0, $headers->get('X-RateLimit-Remaining'), "X-RateLimit-Remaining:$i");
                $this->assertEquals(60, $headers->get('Retry-After'), "Retry-After:$i");
            }
        }

        usleep(1000000);

        try {
            $throttled = null;
            $this->dispatch('/route-throttled');
        } catch (ThrottledException $throttled) {
        }

        $this->assertInstanceOf(ThrottledException::class, $throttled);

        $response = $throttled->createResponse();

        $this->assertEquals($status, $response->getStatusCode());
        $this->assertEquals($msg, $response->getContent());
        $this->assertEquals(10, $response->getHeaders()->get('X-RateLimit-Limit'));
        $this->assertEquals(0, $response->getHeaders()->get('X-RateLimit-Remaining'));
        $this->assertLessThanOrEqual(59, $response->getHeaders()->get('Retry-After'));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testWrongImplementedMiddleware()
    {
        new StubThrolledWrongImplemented(StubController::class, 0);
    }
}

class StubThrolledWrongImplemented extends Throttle{}
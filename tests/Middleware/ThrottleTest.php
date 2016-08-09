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
        // Clear File Cache
        $files = glob(__DIR__ . '/../.data/*'); // get all file names
        foreach ($files as $file) { // iterate files
            if (is_file($file)) {
                clearstatcache(null, $file);
                clearstatcache(false, $file);
                clearstatcache(true, $file);
                unlink($file); // delete file
            }
        }
        clearstatcache();
        clearstatcache(false);
        clearstatcache(true);

        $this->app->useImplicitView(false);

        $this->app->router->addGet('/', [
            'namespace'  => 'Stub',
            'controller' => 'Stubthrottled',
            'action'     => 'index'
        ]);

        $msg    = StatusCode::message(StatusCode::TOO_MANY_REQUESTS);
        $status = StatusCode::TOO_MANY_REQUESTS . ' ' . $msg;
        for ($i = 0; $i < 11; $i++) {
            // WHEN
            $this->app->handle('/');
            $response = $this->app->response;
            if ($i < 10) {
                $this->assertNotEquals($status, $response->getStatusCode());
                $this->assertNotEquals($msg, $response->getContent());
                $this->assertEquals(10, $response->getHeaders()->get('X-RateLimit-Limit'));
                $this->assertEquals(9 - $i, $response->getHeaders()->get('X-RateLimit-Remaining'));
                $this->assertEquals(null, $response->getHeaders()->get('Retry-After'));
            } else {
                $this->assertEquals($status, $response->getStatusCode());
                $this->assertEquals($msg, $response->getContent());
                $this->assertEquals(10, $response->getHeaders()->get('X-RateLimit-Limit'));
                $this->assertEquals(0, $response->getHeaders()->get('X-RateLimit-Remaining'));
                $this->assertEquals(60, $response->getHeaders()->get('Retry-After'));
            }
        }

        usleep(1000000);
        $this->app->handle('/');

        $response = $this->app->response;

        $this->assertEquals($status, $response->getStatusCode());
        $this->assertEquals($msg, $response->getContent());
        $this->assertEquals(10, $response->getHeaders()->get('X-RateLimit-Limit'));
        $this->assertEquals(0, $response->getHeaders()->get('X-RateLimit-Remaining'));
        $this->assertEquals(59, $response->getHeaders()->get('Retry-After'));
    }
}


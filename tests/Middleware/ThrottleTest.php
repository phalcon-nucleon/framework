<?php
namespace Middleware;

use Luxury\Constants\Services;
use Phalcon\Http\Response;
use Phalcon\Http\Response\StatusCode;
use TestCase\TestCase;

/**
 * Trait ThrottleTest
 *
 * @package Middleware
 */
class ThrottleTest extends TestCase
{

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        global $config;

        $config = array_merge($config, [
            'cache' => [
                'default' => [
                    'adapter' => 'Data', // Files, Memcache, Libmemcached, Redis
                    'driver'  => 'File', // Files, Memcache, Libmemcached, Redis
                    'options' => ['cacheDir' => __DIR__ . '/../.data/'],
                ]
            ]
        ]);
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        global $config;

        $config = [];
    }

    public function setUp()
    {
        parent::setUp();

        $dir = __DIR__ . '/../.data';
        if (!is_dir($dir)) {
            if (!mkdir($dir)) {
                throw new \RuntimeException("Can't made .data directory.");
            }
        }
        // Clear File Cache
        $files = glob($dir . '/*'); // get all file names
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
    }

    public function tearDown()
    {
        parent::tearDown();

        $dir = __DIR__ . '/../.data/';

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            unlink($dir . $item);
        }

        rmdir($dir);
    }

    public function testThrottle()
    {
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

    public function testThrottleFiltered()
    {
        $this->app->useImplicitView(false);

        $this->app->router->addGet('/', [
            'namespace'  => 'Stub',
            'controller' => 'Stubthrottled',
            'action'     => 'index'
        ]);

        $this->app->router->addGet('/throttled', [
            'namespace'  => 'Stub',
            'controller' => 'Stubthrottled',
            'action'     => 'throttled'
        ]);

        $msg    = StatusCode::message(StatusCode::TOO_MANY_REQUESTS);
        $status = StatusCode::TOO_MANY_REQUESTS . ' ' . $msg;
        for ($i = 0; $i < 10; $i++) {
            // WHEN
            $this->app->handle('/');
            $response = $this->app->getDI()->getShared(Services::RESPONSE);

            $this->assertNotEquals($status, $response->getStatusCode());
            $this->assertNotEquals($msg, $response->getContent());
            $this->assertEquals(10, $response->getHeaders()->get('X-RateLimit-Limit'));
            $this->assertEquals(9 - $i, $response->getHeaders()->get('X-RateLimit-Remaining'));
            $this->assertEquals(null, $response->getHeaders()->get('Retry-After'));
        }

        usleep(1000000);

        $this->app->getDI()->remove(Services::RESPONSE);
        $this->app->getDI()->setShared(Services::RESPONSE, function () {
            $response = new Response();
            $response->setHeaders(new Response\Headers());

            return $response;
        });

        $this->app->handle('/throttled');

        $response = $this->app->getDI()->getShared(Services::RESPONSE);

        $this->assertEquals('200 OK', $response->getStatusCode());
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

        $this->app->handle('/');

        $response = $this->app->getDI()->getShared(Services::RESPONSE);

        $this->assertEquals($status, $response->getStatusCode());
        $this->assertEquals($msg, $response->getContent());
        $this->assertEquals(10, $response->getHeaders()->get('X-RateLimit-Limit'));
        $this->assertEquals(0, $response->getHeaders()->get('X-RateLimit-Remaining'));
        $this->assertEquals(60, $response->getHeaders()->get('Retry-After'));
    }
}

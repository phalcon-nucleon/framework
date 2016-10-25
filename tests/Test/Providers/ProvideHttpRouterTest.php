<?php
namespace Test\Providers;

use Luxury\Constants\Services;
use Luxury\Providers\Http\Router;
use Test\Stub\StubKernelHttpEmpty;
use Test\TestCase\TestCase;

/**
 * Trait ProvideCliRouterTest
 *
 * @package Test\Providers
 */
class ProvideHttpRouterTest extends TestCase
{
    protected function kernel()
    {
        return StubKernelHttpEmpty::class;
    }

    public function testRegister()
    {
        $provider = new Router;

        $provider->registering();

        $this->assertTrue($this->getDI()->has(Services::ROUTER));

        $this->assertTrue($this->getDI()->getService(Services::ROUTER)->isShared());

        $this->assertInstanceOf(
            \Phalcon\Mvc\Router::class,
            $this->getDI()->getShared(Services::ROUTER)
        );
    }
}

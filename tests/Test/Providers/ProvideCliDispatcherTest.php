<?php
namespace Test\Providers;

use Luxury\Constants\Services;
use Luxury\Providers\Cli\Dispatcher;
use Test\Stub\StubKernelCliEmpty;
use Test\TestCase\TestCase;

/**
 * Trait ProvideCliRouterTest
 *
 * @package Test\Providers
 */
class ProvideCliDispatcherTest extends TestCase
{
    protected function kernel()
    {
        return StubKernelCliEmpty::class;
    }

    public function testRegister()
    {
        $provider = new Dispatcher();

        $provider->registering();

        $this->assertTrue($this->getDI()->has(Services::DISPATCHER));

        $this->assertTrue($this->getDI()->getService(Services::DISPATCHER)->isShared());

        $this->assertInstanceOf(
            \Phalcon\Cli\Dispatcher::class,
            $this->getDI()->getShared(Services::DISPATCHER)
        );
    }
}

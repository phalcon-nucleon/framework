<?php
namespace Test\Providers;

use Fake\Kernels\Cli\StubKernelCliEmpty;
use Neutrino\Constants\Services;
use Neutrino\Providers\Http\Dispatcher;
use Test\TestCase\TestCase;

/**
 * Trait ProvideCliRouterTest
 *
 * @package Test\Providers
 */
class ProvideHttpDispatcherTest extends TestCase
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
            \Phalcon\Mvc\Dispatcher::class,
            $this->getDI()->getShared(Services::DISPATCHER)
        );
    }
}

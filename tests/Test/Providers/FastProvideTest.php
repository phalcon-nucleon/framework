<?php

namespace Test\Providers;

use Phalcon\Di\Injectable;
use Test\TestCase\TestCase;
use Neutrino\Foundation\Http\Kernel as HttpApplication;

class FastProvideTest extends TestCase
{
    public function testFastRegistration()
    {
        $app = new StubKernelWithFastRegistrationService;

        $app->registerServices();

        $di = $app->getDI();

        $this->assertTrue($di->has('fakeService'));
        $this->assertInstanceOf(FakeService::class, $di->getShared('fakeService'));
        $this->assertInstanceOf(FakeService::class, $di->getShared(FakeService::class));
        $this->assertEquals($di->getShared('fakeService'), $di->getShared(FakeService::class));
    }
}

class StubKernelWithFastRegistrationService extends HttpApplication
{
    protected $providers = [
        'fakeService' => FakeService::class
    ];
}

class FakeService extends Injectable
{

}

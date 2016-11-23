<?php

namespace Test\Providers;

use Neutrino\Constants\Services;
use Neutrino\Providers\Session;
use Phalcon\Session\Adapter\Files;
use Phalcon\Session\Bag;
use Test\Stub\StubKernelHttpEmpty;
use Test\TestCase\TestCase;

/**
 * Class ProviderSessionTest
 *
 * @package Test\Providers
 */
class ProviderSessionTest extends TestCase
{
    protected function kernel()
    {
        return StubKernelHttpEmpty::class;
    }

    public function testRegister()
    {
        $this->app->config->session = new \stdClass();
        $this->app->config->session->adapter = 'Files';

        $provider = new Session();

        $provider->registering();

        $this->assertTrue($this->getDI()->has(Services::SESSION));

        $this->assertTrue($this->getDI()->getService(Services::SESSION)->isShared());

        $this->assertInstanceOf(
            Files::class,
            $this->getDI()->getShared(Services::SESSION)
        );

        $this->assertTrue($this->getDI()->has(Services::SESSION_BAG));

        $this->assertFalse($this->getDI()->getService(Services::SESSION_BAG)->isShared());

        $this->assertInstanceOf(
            Bag::class,
            $this->getDI()->get(Services::SESSION_BAG, ['name' => ''])
        );
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testFailRegister()
    {
        $this->app->config->session = new \stdClass();
        $this->app->config->session->adapter = 'NoValidClass';

        $provider = new Session();

        $provider->registering();

        $this->getDI()->getShared(Services::SESSION);
    }
}

<?php

namespace Test\Providers;

use Fake\Kernels\Http\StubKernelHttpEmpty;
use Neutrino\Constants\Services;
use Neutrino\Providers\Session;
use Phalcon\Config;
use Phalcon\Session\Adapter\Files;
use Phalcon\Session\Bag;
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
        $this->app->config->session = new Config([
            'default' => 'files',
            'stores' => [
                'files' => [
                    'adapter' => 'Files',
                ]
            ]
        ]);

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
    public function testWrongStore()
    {
        $this->app->config->session = new Config([
            'default' => 'foo',
            'stores' => [
                'files' => [
                    'adapter' => 'Files',
                ]
            ]
        ]);

        $provider = new Session();

        $provider->registering();

        $this->getDI()->getShared(Services::SESSION);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testFailRegister()
    {
        $this->app->config->session = new Config([
            'default' => 'my',
            'stores' => [
                'my' => [
                    'adapter' => 'NoValidClass',
                ]
            ]
        ]);

        $provider = new Session();

        $provider->registering();

        $this->getDI()->getShared(Services::SESSION);
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: xlzi590
 * Date: 17/10/2016
 * Time: 11:32
 */

namespace Test\Providers;

use Luxury\Interfaces\Providable;
use Luxury\Providers\Provider;
use Test\TestCase\TestCase;

class ProviderTest extends TestCase
{

    /**
     * @expectedException \RuntimeException
     */
    public function testNoName()
    {
        new StubWrongProvider;
    }

    public function testRegister()
    {
        $provider = new StubRegisterProvider;

        $provider->registering();

        $this->assertTrue($this->getDI()->has('test'));
        $this->assertEquals('test', $this->getDI()->get('test'));
    }
}

class StubWrongProvider extends Provider
{
    /**
     * @return mixed
     */
    protected function register()
    {
        return;
    }
};

class StubRegisterProvider extends Provider
{
    protected $name = 'test';

    /**
     * @return mixed
     */
    protected function register()
    {
        return 'test';
    }
};

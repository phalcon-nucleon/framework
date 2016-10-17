<?php
/**
 * Created by PhpStorm.
 * User: xlzi590
 * Date: 17/10/2016
 * Time: 11:32
 */

namespace Test\Providers;

use Luxury\Providers\Provider;
use Test\TestCase\TestCase;

class ProviderTest extends TestCase
{

    /**
     * @expectedException \RuntimeException
     */
    public function testNoName()
    {
        new class extends Provider
        {

            /**
             * @return mixed
             */
            protected function register()
            {
                return;
            }
        };
    }

    public function testRegister()
    {
        $provider = new class extends Provider
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

        $provider->registering($this->getDI());

        $this->assertTrue($this->getDI()->has('test'));
        $this->assertEquals('test', $this->getDI()->get('test'));
    }

    public function testRegistering()
    {
        $provider = new class extends Provider
        {
            protected $name = 'test';

            public function registering()
            {
                $di = $this->getDI();

                $di->set('test', function () {
                    return 'test';
                });
                $di->set('test.1', function () {
                    return 'test.1';
                });
            }

            /**
             * @return mixed
             */
            protected function register()
            {
                return;
            }
        };

        $provider->registering();

        $this->assertTrue($this->getDI()->has('test'));
        $this->assertTrue($this->getDI()->has('test.1'));
        $this->assertEquals('test', $this->getDI()->get('test'));
        $this->assertEquals('test.1', $this->getDI()->get('test.1'));
    }
}

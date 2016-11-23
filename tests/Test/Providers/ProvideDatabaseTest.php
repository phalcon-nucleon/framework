<?php

namespace Test\Providers;

use Neutrino\Constants\Services;
use Neutrino\Providers\Database;
use Test\Stub\StubKernelHttpEmpty;
use Test\TestCase\TestCase;

/**
 * Class ProvideDatabaseTest
 *
 * @package Test\Providers
 */
class ProvideDatabaseTest extends TestCase
{

    public function setUp()
    {
        global $config;

        $config = array_merge($config, [
            'database' => [
                'adapter' => \Phalcon\Db\Adapter\Pdo\Sqlite::class,
                'options' => [
                    'dbname' => 'dbname'
                ]
            ],
        ]);

        parent::setUp();
    }

    protected function kernel()
    {
        return StubKernelHttpEmpty::class;
    }

    /**
     * @expectedException \Phalcon\Db\Exception
     */
    public function testRegister()
    {
        $provider = new Database();

        $provider->registering();

        $this->assertTrue($this->getDI()->has(Services::DB));

        $this->assertTrue($this->getDI()->getService(Services::DB)->isShared());

        $this->assertInstanceOf(
            \Phalcon\Db\Adapter\Pdo\Sqlite::class,
            $this->getDI()->getShared(Services::DB)
        );
    }
}

<?php

namespace Test\Providers;

use Fake\Kernels\Http\StubKernelHttpEmpty;
use Neutrino\Constants\Services;
use Neutrino\Database\DatabaseStrategy;
use Neutrino\Providers\Database;
use Test\TestCase\TestCase;

/**
 * Class ProvideDatabaseTest
 *
 * @package Test\Providers
 */
class ProvideDatabaseTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        fclose(fopen(__DIR__ . '/fake.sqlite', 'w'));
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        if (file_exists(__DIR__ . '/fake.sqlite')) {
            unlink(__DIR__ . '/fake.sqlite');
        }
    }

    public function setUp()
    {
        self::setConfig([
            'database' => [
                'default'     => 'sqlite',
                'connections' => [
                    'sqlite' => [
                        'adapter' => \Phalcon\Db\Adapter\Pdo\Sqlite::class,
                        'options' => [
                            'dbname' => __DIR__ . '/fake.sqlite'
                        ]
                    ]
                ],
            ]
        ]);

        parent::setUp();
    }

    protected function kernel()
    {
        return StubKernelHttpEmpty::class;
    }

    public function testRegister()
    {
        $provider = new Database();

        $provider->registering();

        $this->assertTrue($this->getDI()->has(Services::DB));
        $this->assertTrue($this->getDI()->has(Services::DB . '.sqlite'));
        $this->assertTrue($this->getDI()->getService(Services::DB)->isShared());
        $this->assertTrue($this->getDI()->getService(Services::DB . '.sqlite')->isShared());
        $this->assertEquals($this->getDI()->getRaw(Services::DB), $this->getDI()->getRaw(Services::DB . '.sqlite'));
    }

    public function testRegisterMultiConnection()
    {
        $this->mockService(Services::CONFIG, new \Phalcon\Config([
            'database' => [
                'default'     => 'sqlite',
                'connections' => [
                    'sqlite'  => [
                        'adapter' => \Phalcon\Db\Adapter\Pdo\Sqlite::class,
                        'options' => [
                            'dbname' => __DIR__ . '/fake.sqlite'
                        ]
                    ],
                    'sqlite2' => [
                        'adapter' => \Phalcon\Db\Adapter\Pdo\Sqlite::class,
                        'options' => [
                            'dbname' => __DIR__ . '/fake.sqlite'
                        ]
                    ]
                ],
            ]
        ]), true);

        $provider = new Database();

        $provider->registering();

        $this->assertTrue($this->getDI()->has(Services::DB));
        $this->assertInstanceOf(DatabaseStrategy::class, $this->getDI()->getShared(Services::DB));

        foreach (['sqlite', 'sqlite2'] as $service) {
            $this->assertTrue($this->getDI()->has(Services::DB . '.' . $service));
            $this->assertTrue($this->getDI()->getService(Services::DB)->isShared());
            $this->assertTrue($this->getDI()->getService(Services::DB . '.' . $service)->isShared());
        }
    }
}

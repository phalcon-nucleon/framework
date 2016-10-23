<?php

namespace Test\Providers;

use Luxury\Constants\Services;
use Luxury\Providers\Logger;
use Phalcon\Config;
use Phalcon\Logger\Adapter\File;
use Test\TestCase\TestCase;

/**
 * Class ProviderLoggerTest
 *
 * @package Test\Providers
 */
class ProviderLoggerTest extends TestCase
{
    public function dataLoggerSetting()
    {
        return [
            'Multiple'   => ['Multiple', 'path', __DIR__ . '/../../.data/'],
            'File'       => ['File', 'path', __DIR__ . '/../../.data/log.txt'],
            'Firelogger' => ['Firelogger', 'name', 'firelogger'],
            'Stream'     => ['Stream', 'name', 'php://stderr'],
            'Syslog'     => ['Syslog', 'name', 'syslog'],
            'Udplogger'  => ['Udplogger', 'name', 'udplogger', ['url' => 'url', 'port' => 1234]],
        ];
    }

    /**
     * @dataProvider dataLoggerSetting
     */
    public function testRegister($adapter, $param, $value, $options = [])
    {
        $this->app->config->log          = new \stdClass();
        $this->app->config->log->adapter = $adapter;
        $this->app->config->log->$param  = $value;
        $this->app->config->log->options = new Config($options);

        $provider = new Logger();

        $provider->registering();

        $this->assertTrue($this->getDI()->has(Services::LOGGER));

        $this->assertTrue($this->getDI()->getService(Services::LOGGER)->isShared());

        if ($adapter === 'Multiple') {
            $this->assertInstanceOf(
                File\Multiple::class,
                $this->getDI()->getShared(Services::LOGGER)
            );
        } else {
            $this->assertInstanceOf(
                '\Phalcon\Logger\Adapter\\' . $adapter,
                $this->getDI()->getShared(Services::LOGGER)
            );
        }

        if ($adapter === 'File') {
            $this->getDI()->getShared(Services::LOGGER)->close();
        }
    }

    public function testRegisterDatabaseLogger()
    {
        // 'Database'   => ['Database', 'name', 'database', ['table' => 'logger']],

        $this->app->config->log          = new \stdClass();
        $this->app->config->log->name    = 'Database';
        $this->app->config->log->adapter = 'Database';
        $this->app->config->log->options = new Config(['table' => 'logger']);

        $this->getDI()->setShared(
            Services::DB,
            $this->getMockBuilder(\Phalcon\Db\Adapter\Pdo\Mysql::class)
                ->disableOriginalConstructor()
                ->getMock()
        );

        $provider = new Logger();

        $provider->registering();

        $this->getDI()->getShared(Services::LOGGER);

        $this->assertInstanceOf(
            \Phalcon\Logger\Adapter\Database::class,
            $this->getDI()->getShared(Services::LOGGER)
        );
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testFailRegisterWrongAdapter()
    {
        $this->app->config->log          = new \stdClass();
        $this->app->config->log->adapter = 'wrong';

        $provider = new Logger();

        $provider->registering();

        $this->getDI()->getShared(Services::LOGGER);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testFailRegisterMissingName()
    {
        $this->app->config->log          = new \stdClass();
        $this->app->config->log->adapter = 'Multiple';

        $provider = new Logger();

        $provider->registering();

        $this->getDI()->getShared(Services::LOGGER);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testFailRegisterMissingOptions()
    {
        $this->app->config->log          = new \stdClass();
        $this->app->config->log->adapter = 'Multiple';
        $this->app->config->log->path    = 'Multiple';

        $provider = new Logger();

        $provider->registering();

        $this->getDI()->getShared(Services::LOGGER);
    }
}

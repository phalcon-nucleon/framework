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
            //'Multiple'   => ['Multiple', 'path', __DIR__ . '/../../.data/'],
            'File'       => ['File', 'path', __DIR__ . '/../../.data/log.txt'],
            //'Firelogger' => ['Firelogger', 'name', 'firelogger'],
            'Stream'     => ['Stream', 'name', 'php://stderr'],
            'Syslog'     => ['Syslog', 'name', 'syslog'],
            //'Udplogger'  => ['Udplogger', 'name', 'udplogger', ['url' => 'url', 'port' => 1234]],
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


            $this->assertInstanceOf(
                '\Phalcon\Logger\Adapter\\' . $adapter,
                $this->getDI()->getShared(Services::LOGGER)
            );

        if ($adapter === 'File') {
            $this->getDI()->getShared(Services::LOGGER)->close();
        }
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
        $this->app->config->log->adapter = 'File';
        $this->app->config->log->path    = 'File';

        $provider = new Logger();

        $provider->registering();

        $this->getDI()->getShared(Services::LOGGER);
    }
}

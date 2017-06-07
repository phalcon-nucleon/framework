<?php

namespace Test\Error\Writer;

use Neutrino\Constants\Services;
use Neutrino\Error\Error;
use Neutrino\Error\Helper;
use Neutrino\Error\Writer\Logger;
use Phalcon\Logger\Adapter\File;
use Test\TestCase\TestCase;

class LoggerTest extends TestCase
{
    public function dataHandle()
    {
        $error = Error::fromException(new \Exception());
        $data[] = [Helper::format($error, false, true), \Phalcon\Logger::ERROR, $error];

        $error = Error::fromError(E_ERROR, 'E_ERROR', __FILE__, __LINE__);
        $data[] = [Helper::format($error, false, true), \Phalcon\Logger::EMERGENCE, $error];

        $error = Error::fromError(E_WARNING, 'E_WARNING', __FILE__, __LINE__);
        $data[] = [Helper::format($error, false, true), \Phalcon\Logger::WARNING, $error];

        $error = Error::fromError(E_NOTICE, 'E_USER_ERROR', __FILE__, __LINE__);
        $data[] = [Helper::format($error, false, true), \Phalcon\Logger::NOTICE, $error];

        $error = Error::fromError(E_STRICT, 'E_STRICT', __FILE__, __LINE__);
        $data[] = [Helper::format($error, false, true), \Phalcon\Logger::INFO, $error];

        $error = Error::fromError(E_PARSE, 'E_PARSE', __FILE__, __LINE__);
        $data[] = [Helper::format($error, false, true), \Phalcon\Logger::CRITICAL, $error];

        $error = Error::fromError(E_USER_ERROR, 'E_USER_ERROR', __FILE__, __LINE__);
        $data[] = [Helper::format($error, false, true), \Phalcon\Logger::ERROR, $error];

        return $data;
    }

    /**
     * @dataProvider dataHandle
     *
     * @param $expectedMessage
     * @param $expectedMethod
     * @param $error
     */
    public function testHandleWhitoutConfig($expectedMessage, $expectedMethod, $error)
    {
        $mock = $this->mockService(Services::LOGGER, File::class, true);

        $mock->expects($this->once())
            ->method('log')
            ->with($expectedMethod, $expectedMessage);

        $mock->expects($this->never())
            ->method('setFormatter');

        $writer = new Logger();

        $writer->handle($error);
    }

    public function dataHandleMultiConfig()
    {
        $configs = [
            ['error' => [
                'formatter' => new \Phalcon\Logger\Formatter\Line('[%date%][%type%] %message%', 'Y-m-d H:i:s O'),
            ]],
            ['error' => [
                'formatter' => [
                    'formatter'  => \Phalcon\Logger\Formatter\Line::class,
                    'format'     => '[%date%][%type%] %message%',
                    'dateFormat' => 'Y-m-d H:i:s O'
                ],
            ]],
            ['error' => [
                'formatter' => [
                    'formatter'  => \Phalcon\Logger\Formatter\Line::class,
                    'format'     => '[%date%][%type%] %message%',
                    'date_format' => 'Y-m-d H:i:s O'
                ],
            ]],
            ['error' => [
                'formatter' => [
                    'formatter'  => \Phalcon\Logger\Formatter\Line::class,
                    'format'     => '[%date%][%type%] %message%',
                    'date' => 'Y-m-d H:i:s O'
                ],
            ]]
        ];

        $datas = $this->dataHandle();

        $handles = [];
        foreach ($configs as $config) {
            foreach ($datas as $data) {
                $data[] = $config;
                $handles[] = $data;
            }
        }

        return $handles;
    }

    /**
     * @dataProvider dataHandleMultiConfig
     *
     * @param $expectedMessage
     * @param $expectedMethod
     * @param $error
     */
    public function testHandleWhitConfig($expectedMessage, $expectedMethod, $error, $config)
    {
        $logger = $this->mockService(Services::LOGGER, File::class, true);

        $this->mockService(Services::CONFIG, new \Phalcon\Config($config), true);

        $logger->expects($this->once())
            ->method('log')
            ->with($expectedMethod, $expectedMessage);

        $logger->expects($this->once())
            ->method('setFormatter');

        $writer = new Logger();

        $writer->handle($error);
    }
}
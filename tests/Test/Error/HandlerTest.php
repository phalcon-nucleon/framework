<?php

namespace Test\Error;

use Luxury\Constants\Services;
use Luxury\Error\Error;
use Luxury\Error\Handler;
use Luxury\Foundation\Controller;
use Phalcon\Logger;
use Test\TestCase\TestCase;

/**
 * Class HandlerTest
 *
 * @package Test\Error
 */
class HandlerTest extends TestCase
{
    /**
     * @return array
     */
    public function dataLogType()
    {
        return [
            'E_PARSE'             => [E_PARSE, Logger::CRITICAL],
            'E_COMPILE_ERROR'     => [E_COMPILE_ERROR, Logger::EMERGENCY],
            'E_CORE_ERROR'        => [E_CORE_ERROR, Logger::EMERGENCY],
            'E_ERROR'             => [E_ERROR, Logger::EMERGENCY],
            'E_RECOVERABLE_ERROR' => [E_RECOVERABLE_ERROR, Logger::ERROR],
            'E_USER_ERROR'        => [E_USER_ERROR, Logger::ERROR],
            'E_WARNING'           => [E_WARNING, Logger::WARNING],
            'E_USER_WARNING'      => [E_USER_WARNING, Logger::WARNING],
            'E_CORE_WARNING'      => [E_CORE_WARNING, Logger::WARNING],
            'E_COMPILE_WARNING'   => [E_COMPILE_WARNING, Logger::WARNING],
            'E_NOTICE'            => [E_NOTICE, Logger::NOTICE],
            'E_USER_NOTICE'       => [E_USER_NOTICE, Logger::NOTICE],
            'E_STRICT'            => [E_STRICT, Logger::INFO],
            'E_DEPRECATED'        => [E_DEPRECATED, Logger::INFO],
            'E_USER_DEPRECATED'   => [E_USER_DEPRECATED, Logger::INFO],
            'null'                => [null, Logger::ERROR],
        ];
    }

    /**
     * @dataProvider dataLogType
     *
     * @param $errorType
     * @param $logType
     */
    public function testGetLogType($errorType, $logType)
    {
        $this->assertEquals($logType, Handler::getLogType($errorType));
    }

    public function dataErrorType()
    {
        return [
            '1234'                => [1234, '1234'],
            'Uncaught exception'  => [0, 'Uncaught exception'],
            'E_ERROR'             => [E_ERROR, 'E_ERROR'],
            'E_WARNING'           => [E_WARNING, 'E_WARNING'],
            'E_PARSE'             => [E_PARSE, 'E_PARSE'],
            'E_NOTICE'            => [E_NOTICE, 'E_NOTICE'],
            'E_CORE_ERROR'        => [E_CORE_ERROR, 'E_CORE_ERROR'],
            'E_CORE_WARNING'      => [E_CORE_WARNING, 'E_CORE_WARNING'],
            'E_COMPILE_ERROR'     => [E_COMPILE_ERROR, 'E_COMPILE_ERROR'],
            'E_COMPILE_WARNING'   => [E_COMPILE_WARNING, 'E_COMPILE_WARNING'],
            'E_USER_ERROR'        => [E_USER_ERROR, 'E_USER_ERROR'],
            'E_USER_WARNING'      => [E_USER_WARNING, 'E_USER_WARNING'],
            'E_USER_NOTICE'       => [E_USER_NOTICE, 'E_USER_NOTICE'],
            'E_STRICT'            => [E_STRICT, 'E_STRICT'],
            'E_RECOVERABLE_ERROR' => [E_RECOVERABLE_ERROR, 'E_RECOVERABLE_ERROR'],
            'E_DEPRECATED'        => [E_DEPRECATED, 'E_DEPRECATED'],
            'E_USER_DEPRECATED'   => [E_USER_DEPRECATED, 'E_USER_DEPRECATED'],
        ];
    }

    /**
     * @dataProvider dataErrorType
     *
     * @param $code
     * @param $type
     */
    public function testGetErrorType($code, $type)
    {
        $this->assertEquals($type, Handler::getErrorType($code));
    }

    public function testHandle()
    {
        $logger = $this->getMockBuilder(Logger\Adapter\File::class)
            ->disableOriginalConstructor()
            ->setMethods(['setFormatter', 'log'])
            ->getMock();

        $logger->expects($this->any())->method('setFormatter');
        $logger->expects($this->any())->method('log');

        $this->getDI()->setShared(Services::LOGGER, $logger);

        $this->getDI()->getShared(Services::CONFIG)->error = [
            'formatter'  => [
                'formatter'  => \Phalcon\Logger\Formatter\Line::class,
                'format'     => '[%date%][%type%] %message%',
                'dateFormat' => 'Y-m-d H:i:s O'
            ],
            'namespace'  => __NAMESPACE__,
            'controller' => 'Stuberror',
            'action'     => 'index',
        ];

        Handler::handle(new Error([
            'type'    => E_ERROR,
            'message' => __METHOD__,
            'file'    => __FILE__,
            'line'    => __LINE__,
            'isError' => true,
        ]));
    }
}

class StuberrorController extends Controller
{
    /**
     * Event called on controller construction
     *
     * Register middleware here.
     */
    protected function onConstruct()
    {
    }

    public function indexAction()
    {
    }
}

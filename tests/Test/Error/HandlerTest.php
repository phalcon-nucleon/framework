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
    public function setUp()
    {
        parent::setUp();

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
    }

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

    public function dataHandleError()
    {
        $datas = [
            'null'                => [null, Logger::ERROR],
            'E_PARSE'             => [E_PARSE, Logger::CRITICAL],
            'E_COMPILE_ERROR'     => [E_COMPILE_ERROR, Logger::EMERGENCY],
            'E_CORE_ERROR'        => [E_CORE_ERROR, Logger::EMERGENCY],
            'E_ERROR'             => [E_ERROR, Logger::EMERGENCY],
            'E_RECOVERABLE_ERROR' => [E_RECOVERABLE_ERROR, Logger::ERROR],
            'E_USER_ERROR'        => [E_USER_ERROR, Logger::ERROR],
        ];

        foreach ($datas as &$data) {
            $data[] =
                Handler::getErrorType($data[0]) . ': ' . __CLASS__ . '::{{__FUNCTION__}} in ' . __FILE__ . ' on line 120';
        }

        return $datas;
    }

    public function mockLogger($expectedLogger, $expectedMessage)
    {
        $logger = $this->getMockBuilder(Logger\Adapter\File::class)
            ->disableOriginalConstructor()
            ->setMethods(['setFormatter', 'log'])
            ->getMock();

        $logger->expects($this->any())->method('setFormatter');

        $logger->expects($this->any())->method('log')->with($expectedLogger, $expectedMessage);

        $this->getDI()->setShared(Services::LOGGER, $logger);
    }

    /**
     * @dataProvider dataHandleError
     */
    public function testHandleErrorWithoutView($errorCode, $expectedLogger, $expectedMessage)
    {
        $expectedMessage = str_replace('{{__FUNCTION__}}', __FUNCTION__, $expectedMessage);

        $this->mockLogger($expectedLogger, $expectedMessage);

        $this->expectOutputString($expectedMessage);

        Handler::handle(new Error([
            'type'    => $errorCode,
            'message' => __METHOD__,
            'file'    => __FILE__,
            'line'    => 120,
            'isError' => true,
        ]));
    }

    /**
     * @dataProvider dataHandleError
     */
    public function testHandleErrorWithView($errorCode, $expectedLogger, $expectedMessage)
    {
        $expectedMessage = str_replace('{{__FUNCTION__}}', __FUNCTION__, $expectedMessage);

        $this->mockLogger($expectedLogger, $expectedMessage);

        $view = $this->getMockBuilder(\Phalcon\Mvc\View::class)
            ->disableOriginalConstructor()
            ->setMethods(['start', 'render', 'finish', 'getContent'])
            ->getMock();
        $view->expects($this->any())->method('start');
        $view->expects($this->any())->method('render');
        $view->expects($this->any())->method('finish');
        $view->expects($this->any())->method('getContent')->willReturn($expectedMessage);

        $this->getDI()->setShared(Services::VIEW, $view);

        $this->expectOutputString($expectedMessage);

        $response = Handler::handle(new Error([
            'type'    => $errorCode,
            'message' => __METHOD__,
            'file'    => __FILE__,
            'line'    => 120,
            'isError' => true,
        ]));

        $this->assertTrue($response->isSent());
        $this->assertEquals($expectedMessage, $response->getContent());
    }

    public function dataHandleWarning()
    {
        $datas = [
            'E_WARNING'         => [E_WARNING, Logger::WARNING],
            'E_USER_WARNING'    => [E_USER_WARNING, Logger::WARNING],
            'E_CORE_WARNING'    => [E_CORE_WARNING, Logger::WARNING],
            'E_COMPILE_WARNING' => [E_COMPILE_WARNING, Logger::WARNING],
            'E_NOTICE'          => [E_NOTICE, Logger::NOTICE],
            'E_USER_NOTICE'     => [E_USER_NOTICE, Logger::NOTICE],
            'E_STRICT'          => [E_STRICT, Logger::INFO],
            'E_DEPRECATED'      => [E_DEPRECATED, Logger::INFO],
            'E_USER_DEPRECATED' => [E_USER_DEPRECATED, Logger::INFO],
        ];

        foreach ($datas as &$data) {
            $data[] =
                Handler::getErrorType($data[0]) . ': ' . __CLASS__ . '::testHandleWarning in ' . __FILE__ . ' on line 120';
        }

        return $datas;
    }

    /**
     * @dataProvider dataHandleWarning
     */
    public function testHandleWarning($errorCode, $expectedLogger, $expectedMessage)
    {
        $this->mockLogger($expectedLogger, $expectedMessage);

        $this->expectOutputString('');

        Handler::handle(new Error([
            'type'    => $errorCode,
            'message' => __METHOD__,
            'file'    => __FILE__,
            'line'    => 120,
            'isError' => true,
        ]));
    }

    public function testTriggerError()
    {
        $expectedMsg = 'E_USER_ERROR: msg in ' . __FILE__ . ' on line ' . (__LINE__ + 8);

        $this->expectOutputString($expectedMsg);

        $this->mockLogger(Logger::ERROR, $expectedMsg);

        Handler::register();

        trigger_error('msg', E_USER_ERROR);
    }
}

class StuberrorController extends Controller
{
    protected function onConstruct()
    {
    }

    public function indexAction()
    {
    }
}

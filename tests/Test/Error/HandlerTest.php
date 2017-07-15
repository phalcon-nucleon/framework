<?php

namespace Test\Error;

use Neutrino\Constants\Services;
use Neutrino\Error\Error;
use Neutrino\Error\Handler;
use Neutrino\Error\Helper;
use Neutrino\Error\Writer as ErrorWriter;
use Neutrino\Http\Controller;
use Phalcon\Logger;
use Test\TestCase\TestCase;

/**
 * Class HandlerTest
 *
 * @package Test\Error
 */
class HandlerTest extends TestCase
{
    private static $defaultErrorLog;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$defaultErrorLog = ini_get('error_log');
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        ini_set('error_log', self::$defaultErrorLog);
    }

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

    public function tearDown()
    {
        parent::tearDown();

        if (file_exists(__DIR__ . '/error.log')) {
            unlink(__DIR__ . '/error.log');
        }
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
        $this->assertEquals($logType, Helper::getLogType($errorType));
    }

    public function dataErrorType()
    {
        return [
            '1234'                => [1234, '1234'],
            'Uncaught exception'  => [-1, 'Uncaught exception'],
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
        $this->assertEquals($type, Helper::getErrorType($code));
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

        return $datas;
    }

    public function mockLogger($expectedLogger, $expectedMessage)
    {
        $logger = $this->mockService(Services::LOGGER, Logger\Adapter\File::class, true);

        $logger->expects($this->once())->method('setFormatter');
        $logger->expects($this->once())->method('log')->with($expectedLogger, $expectedMessage);
    }

    /**
     * @dataProvider dataHandleError
     */
    public function testHandleErrorWithoutView($errorCode, $expectedLogger)
    {
        Handler::setWriters([ErrorWriter\Logger::class, ErrorWriter\View::class]);

        $error = new Error([
            'type'    => is_null($errorCode) ? -1 : $errorCode,
            'code'    => $errorCode,
            'message' => __METHOD__,
            'file'    => __FILE__,
            'line'    => 120,
            'isError' => true,
        ]);

        $expectedMessage = Helper::format($error);

        $this->mockLogger($expectedLogger, $expectedMessage);

        $this->expectOutputString($expectedMessage);

        Handler::handle($error);
    }

    /**
     * @dataProvider dataHandleError
     */
    public function testHandleErrorWithView($errorCode, $expectedLogger)
    {
        Handler::setWriters([ErrorWriter\Logger::class, ErrorWriter\View::class]);

        $error = new Error([
            'type'    => is_null($errorCode) ? -1 : $errorCode,
            'code'    => $errorCode,
            'message' => __METHOD__,
            'file'    => __FILE__,
            'line'    => 120,
            'isError' => true,
        ]);

        $expectedMessage = Helper::format($error);

        $this->mockLogger($expectedLogger, $expectedMessage);

        $view = $this->mockService(Services::VIEW, \Phalcon\Mvc\View::class, true);

        $view->expects($this->any())->method('start');
        $view->expects($this->any())->method('render');
        $view->expects($this->any())->method('finish');
        $view->expects($this->any())->method('getContent')->willReturn($expectedMessage);

        $this->expectOutputString($expectedMessage);

        Handler::handle($error);
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

        return $datas;
    }

    /**
     * @dataProvider dataHandleWarning
     */
    public function testHandleWarning($errorCode, $expectedLogger)
    {
        Handler::setWriters([ErrorWriter\Logger::class, ErrorWriter\View::class]);

        $error = new Error([
            'type'    => $errorCode,
            'message' => __METHOD__,
            'file'    => __FILE__,
            'line'    => 120,
            'isError' => true,
        ]);

        $expectedMessage = Helper::format($error);

        $this->mockLogger($expectedLogger, $expectedMessage);

        $this->expectOutputString('');

        Handler::handle($error);
    }

    public function testHandleException()
    {
        Handler::setWriters([ErrorWriter\Logger::class, ErrorWriter\View::class]);

        $e = new \Exception();

        $msg = Helper::format(new Error([
            'type'        => -1,
            'code'        => $e->getCode(),
            'message'     => $e->getMessage(),
            'file'        => $e->getFile(),
            'line'        => $e->getLine(),
            'isException' => true,
            'exception'   => $e,
        ]));

        $this->mockLogger(Logger::ERROR, $msg);

        $this->expectOutputString($msg);

        Handler::handleException($e);
    }

    public function testHandleError()
    {
        Handler::setWriters([ErrorWriter\Logger::class, ErrorWriter\View::class]);

        $msg = str_replace(DIRECTORY_SEPARATOR, '/', "E_USER_ERROR\n  Message : user error\n in : " . __FILE__ . '(' . (__LINE__ + 6).')');

        $this->mockLogger(Logger::ERROR, $msg);

        $this->expectOutputString($msg);

        Handler::handleError(E_USER_ERROR, 'user error', __FILE__, __LINE__);
    }

    public function testTriggerError()
    {
        Handler::setWriters([ErrorWriter\Phplog::class, ErrorWriter\Logger::class, ErrorWriter\View::class]);

        $cur_error_log = ini_get('error_log');
        ini_set('error_log', __DIR__ . '/error.log');

        $expectedMsg = str_replace(DIRECTORY_SEPARATOR, '/', "E_USER_ERROR\n  Message : msg\n in : " . __FILE__ . '(' . (__LINE__ + 9).')');

        $this->expectOutputString($expectedMsg);

        $this->mockLogger(Logger::ERROR, $expectedMsg);

        Handler::register();

        $date = date('d-M-Y H:i:s e');
        trigger_error('msg', E_USER_ERROR);

        ini_set('error_log', $cur_error_log);
        restore_error_handler();
        restore_exception_handler();

        $r = fopen(__DIR__ . '/error.log', 'r');

        $lines = [];
        while (!feof($r)) {
            if (($str = fgets($r)) !== false) {
                $lines[] = trim($str, "\n\r");
            }
        }
        fclose($r);
        unlink(__DIR__ . '/error.log');
        $this->assertCount(3, $lines);
        $this->assertEquals('[' . $date . '] ' . $expectedMsg, implode("\n", $lines));
    }

    public function testTriggerErrorDefaultWriter()
    {
        Handler::setWriters([ErrorWriter\Phplog::class]);

        $cur_error_log = ini_get('error_log');
        ini_set('error_log', __DIR__ . '/error.log');

        $expectedMsg = str_replace(DIRECTORY_SEPARATOR, '/', "E_USER_ERROR\n  Message : msg\n in : " . __FILE__ . '(' . (__LINE__ + 7).')');

        $this->expectOutputString(null);

        Handler::register();

        $date = date('d-M-Y H:i:s e');
        trigger_error('msg', E_USER_ERROR);

        ini_set('error_log', $cur_error_log);
        restore_error_handler();
        restore_exception_handler();

        $r = fopen(__DIR__ . '/error.log', 'r');

        $lines = [];
        while (!feof($r)) {
            if (($str = fgets($r)) !== false) {
                $lines[] = trim($str, "\n\r");
            }
        }
        fclose($r);
        unlink(__DIR__ . '/error.log');
        $this->assertCount(3, $lines);
        $this->assertEquals('[' . $date . '] ' . $expectedMsg, implode("\n", $lines));
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

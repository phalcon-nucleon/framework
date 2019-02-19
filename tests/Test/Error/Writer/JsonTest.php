<?php

namespace Test\Error\Writer;

use Neutrino\Constants\Services;
use Neutrino\Error\Error;
use Neutrino\Error\Helper;
use Neutrino\Error\Writer\Json;
use Test\TestCase\TestCase;

class JsonTest extends TestCase
{
    public function dataHandle()
    {
        $error = Error::fromException(new \Exception());
        $data[] = [true, $error];

        $error = Error::fromError(E_ERROR, 'E_ERROR', __FILE__, __LINE__);
        $data[] = [true, $error];

        $error = Error::fromError(E_WARNING, 'E_WARNING', __FILE__, __LINE__);
        $data[] = [null, $error];

        $error = Error::fromError(E_NOTICE, 'E_USER_ERROR', __FILE__, __LINE__);
        $data[] = [null, $error];

        $error = Error::fromError(E_STRICT, 'E_STRICT', __FILE__, __LINE__);
        $data[] = [null, $error];

        $error = Error::fromError(E_PARSE, 'E_PARSE', __FILE__, __LINE__);
        $data[] = [true, $error];

        return $data;
    }

    /**
     * @dataProvider dataHandle
     *
     * @param $expected
     * @param $error
     */
    public function testHandle($expected, $error)
    {
        $mock = $this->mockService(Services::RESPONSE, \Phalcon\Http\Response::class, true);

        if ($expected) {
            $mock->expects($this->once())
                ->method('isSent')
                ->will($this->returnValue(false));
            $mock->expects($this->once())
                ->method('setJsonContent')
                ->with([
                    'code' => 500,
                    'status' => 'Internal Server Error',
                    'debug' => $error
                ])
                ->will($this->returnSelf());
            $mock->expects($this->once())
                ->method('setStatusCode')
                ->with(500)
                ->will($this->returnSelf());
            $mock->expects($this->once())
                ->method('send');
        } else {
            $mock->expects($this->never())
                ->method('isSent');
            $mock->expects($this->never())
                ->method('setJsonContent');
            $mock->expects($this->never())
                ->method('setStatusCode');
            $mock->expects($this->never())
                ->method('send');
        }

        $writer = new Json();

        $writer->handle($error);
    }

    /**
     * @dataProvider dataHandle
     *
     * @param $expected
     * @param $error
     */
    public function testHandleResponseAlearySent($expected, $error)
    {
        $mock = $this->mockService(Services::RESPONSE, \Phalcon\Http\Response::class, true);

        if ($expected) {

            $mock->expects($this->once())
                ->method('isSent')
                ->will($this->returnValue(true));

            $mock->expects($this->never())
                ->method('setJsonContent');
            $mock->expects($this->never())
                ->method('setStatusCode');
            $mock->expects($this->never())
                ->method('send');

            $this->expectOutputString(json_encode([
                'code' => 500,
                'status' => 'Internal Server Error',
                'debug' => $error
            ]));

        } else {
            $mock->expects($this->never())
                ->method('isSent');
            $mock->expects($this->never())
                ->method('setJsonContent');
            $mock->expects($this->never())
                ->method('setStatusCode');
            $mock->expects($this->never())
                ->method('send');
        }

        $writer = new Json();

        $writer->handle($error);
    }
}

<?php

namespace Test\Error\Writer;

use Neutrino\Constants\Services;
use Neutrino\Error\Error;
use Neutrino\Error\Helper;
use Neutrino\Error\Writer\Flash;
use Test\TestCase\TestCase;

class FlashTest extends TestCase
{
    public function dataHandle()
    {
        $error = Error::fromException(new \Exception());
        $data[] = [Helper::format($error), 'error', $error];

        $error = Error::fromError(E_ERROR, 'E_ERROR', __FILE__, __LINE__);
        $data[] = [Helper::format($error), 'error', $error];

        $error = Error::fromError(E_WARNING, 'E_WARNING', __FILE__, __LINE__);
        $data[] = [Helper::format($error), 'warning', $error];

        $error = Error::fromError(E_NOTICE, 'E_USER_ERROR', __FILE__, __LINE__);
        $data[] = [Helper::format($error), 'notice', $error];

        $error = Error::fromError(E_STRICT, 'E_STRICT', __FILE__, __LINE__);
        $data[] = [Helper::format($error), 'notice', $error];

        $error = Error::fromError(E_PARSE, 'E_PARSE', __FILE__, __LINE__);
        $data[] = [Helper::format($error), 'error', $error];

        $error = Error::fromError(E_USER_ERROR, 'E_USER_ERROR', __FILE__, __LINE__);
        $data[] = [Helper::format($error), 'error', $error];

        return $data;
    }

    /**
     * @dataProvider dataHandle
     *
     * @param $expectedMessage
     * @param $expectedMethod
     * @param $error
     */
    public function testHandle($expectedMessage, $expectedMethod, $error)
    {
        $mock = $this->mockService(Services::FLASH, \Phalcon\Flash\Direct::class, true);

        $mock->expects($this->once())
            ->method($expectedMethod)
            ->with($expectedMessage);

        $writer = new Flash();

        $writer->handle($error);
    }
}
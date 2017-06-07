<?php

namespace Test\Error\Writer;

use Neutrino\Cli\Output\Writer;
use Neutrino\Constants\Services;
use Neutrino\Error\Error;
use Neutrino\Error\Helper;
use Neutrino\Error\Writer\Cli;
use Test\TestCase\TestCase;

class CliTest extends TestCase
{

    public function dataHandle()
    {
        $error = Error::fromException(new \Exception());
        $data[] = [Helper::format($error, false, true), 'error', $error];

        $error = Error::fromError(E_ERROR, 'E_ERROR', __FILE__, __LINE__);
        $data[] = [Helper::format($error, false, true), 'error', $error];

        $error = Error::fromError(E_WARNING, 'E_WARNING', __FILE__, __LINE__);
        $data[] = [null, 'warning', $error];

        $error = Error::fromError(E_NOTICE, 'E_USER_ERROR', __FILE__, __LINE__);
        $data[] = [null, 'notice', $error];

        $error = Error::fromError(E_STRICT, 'E_STRICT', __FILE__, __LINE__);
        $data[] = [null, 'notice', $error];

        $error = Error::fromError(E_PARSE, 'E_PARSE', __FILE__, __LINE__);
        $data[] = [Helper::format($error, false, true), 'error', $error];

        $error = Error::fromError(E_USER_ERROR, 'E_USER_ERROR', __FILE__, __LINE__);
        $data[] = [Helper::format($error, false, true), 'error', $error];

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
        $mock = $this->mockService(Services\Cli::OUTPUT, Writer::class, true);

        $with = [];

        if (!empty($expectedMessage)) {
            $lines = explode("\n", $expectedMessage);

            $maxlen = 0;
            foreach ($lines as $line) {
                $maxlen = max($maxlen, strlen($line));
            }

            $with[] = [str_repeat(' ', $maxlen + 4)];
            foreach ($lines as $line) {
                $with[] = ['  ' . str_pad($line, $maxlen, ' ', STR_PAD_RIGHT) . '  '];
            }
            $with[] = [str_repeat(' ', $maxlen + 4)];
        }

        $mock->expects($this->exactly(count($with)))
            ->method('warn')
            ->withConsecutive(...$with);

        $writer = new Cli();

        $writer->handle($error);
    }

    /**
     * @dataProvider dataHandle
     *
     * @param $expectedMessage
     * @param $expectedMethod
     * @param $error
     */
    public function testHandleNoService($expectedMessage, $expectedMethod, $error)
    {
        if ($this->getDI()->has(Services\Cli::OUTPUT)) {
            $outputCli = $this->getDI()->getShared(Services\Cli::OUTPUT);

            $this->getDI()->remove(Services\Cli::OUTPUT);
        }

        ob_start();

        $writer = new Cli();

        $writer->handle($error);

        $str = ob_get_clean();

        if (!empty($outputCli)) {
            $this->getDI()->setShared(Services\Cli::OUTPUT, $outputCli);
        }

        $this->assertEquals($expectedMessage, $str);
    }
}
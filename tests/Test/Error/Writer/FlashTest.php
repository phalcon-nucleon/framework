<?php

declare(strict_types=1);

namespace Test\Error\Writer;

use Neutrino\Constants\Services;
use Neutrino\Error\Error;
use Neutrino\Error\Helper;
use Neutrino\Error\Writer\Flash;
use Phalcon\Flash\Direct;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase\TestCase;

final class FlashTest extends TestCase
{
    /**
     * @return iterable<array{int, string}>
     */
    public static function types(): iterable
    {
        yield [E_USER_ERROR, 'error'];
        yield [E_WARNING, 'warning'];
        yield [E_NOTICE, 'notice'];
        yield [E_DEPRECATED, 'notice'];
    }

    #[DataProvider('types')]
    public function testHandle(int $type, string $method): void
    {
        $error = Error::fromError($type, 'msg', __FILE__, __LINE__);
        $flash = $this->mockService(Services::FLASH, Direct::class);
        $flash->expects($this->once())->method($method)->with(Helper::format($error));

        (new Flash())->handle($error);
    }

    public function testFatalErrorsLeftToTheErrorPage(): void
    {
        $flash = $this->mockService(Services::FLASH, Direct::class);
        $flash->expects($this->never())->method($this->anything());

        (new Flash())->handle(Error::fromException(new \RuntimeException('boom')));
        (new Flash())->handle(Error::fromError(E_ERROR, 'fatal'));
        (new Flash())->handle(Error::fromError(E_PARSE, 'fatal'));
    }

    public function testWithoutFlashService(): void
    {
        $this->getDI()->remove(Services::FLASH);

        $this->expectOutputString('');

        (new Flash())->handle(Error::fromError(E_WARNING, 'msg'));
    }
}

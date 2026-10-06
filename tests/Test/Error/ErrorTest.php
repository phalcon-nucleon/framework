<?php

declare(strict_types=1);

namespace Test\Error;

use Neutrino\Error\Error;
use Neutrino\Error\Helper;
use Phalcon\Logger\Enum;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ErrorTest extends TestCase
{
    public function testFromError(): void
    {
        $error = Error::fromError(E_WARNING, 'msg', __FILE__, 12);

        $this->assertSame(E_WARNING, $error->type);
        $this->assertSame(E_WARNING, $error->code);
        $this->assertSame('msg', $error->message);
        $this->assertSame(__FILE__, $error->file);
        $this->assertSame(12, $error->line);
        $this->assertNull($error->exception);
        $this->assertTrue($error->isError);
        $this->assertFalse($error->isException);
        $this->assertSame('Warning [E_WARNING]', $error->typeStr);
        $this->assertSame(Enum::WARNING, $error->logLvl);
        $this->assertFalse($error->isFatal());
    }

    public function testFromException(): void
    {
        $exception = new RuntimeException('boom', 42);
        $error = Error::fromException($exception);

        $this->assertSame(Error::EXCEPTION, $error->type);
        $this->assertSame(42, $error->code);
        $this->assertSame('boom', $error->message);
        $this->assertSame($exception->getLine(), $error->line);
        $this->assertSame($exception, $error->exception);
        $this->assertTrue($error->isException);
        $this->assertFalse($error->isError);
        $this->assertSame('Uncaught exception', $error->typeStr);
        $this->assertSame(Enum::ERROR, $error->logLvl);
        $this->assertTrue($error->isFatal());
    }

    public function testStringCodeOfAnException(): void
    {
        $exception = new class ('SQL') extends \PDOException {
            protected $code = 'HY000';
        };

        $this->assertSame('HY000', Error::fromException($exception)->code);
    }

    public function testIsFatal(): void
    {
        foreach ([E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_RECOVERABLE_ERROR] as $type) {
            $this->assertTrue(Error::fromError($type, '')->isFatal(), Helper::getErrorType($type));
        }

        foreach ([E_WARNING, E_NOTICE, E_USER_ERROR, E_USER_WARNING, E_DEPRECATED, E_CORE_WARNING] as $type) {
            $this->assertFalse(Error::fromError($type, '')->isFatal(), Helper::getErrorType($type));
        }
    }

    public function testIsReadonly(): void
    {
        $error = Error::fromError(E_WARNING, 'msg');

        $this->expectException(\Error::class);

        $error->message = 'other'; // @phpstan-ignore property.readOnlyAssignOutOfClass
    }

    public function testJsonSerialize(): void
    {
        $json = json_decode((string) json_encode(Error::fromError(E_NOTICE, 'msg', 'file.php', 3)), true);

        $this->assertSame([
            'type'        => E_NOTICE,
            'code'        => E_NOTICE,
            'message'     => 'msg',
            'file'        => 'file.php',
            'line'        => 3,
            'isException' => false,
            'isError'     => true,
            'typeStr'     => 'Notice [E_NOTICE]',
            'logLvl'      => Enum::NOTICE,
            'exception'   => null,
        ], $json);

        $json = json_decode((string) json_encode(Error::fromException(new RuntimeException('boom', 3))), true);

        $this->assertIsArray($json);
        $this->assertSame(RuntimeException::class, $json['exception']['class']);
        $this->assertSame(3, $json['exception']['code']);
        $this->assertSame('boom', $json['exception']['message']);
        $this->assertNotEmpty($json['exception']['traces']);
    }
}

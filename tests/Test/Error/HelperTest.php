<?php

declare(strict_types=1);

namespace Test\Error;

use Neutrino\Error\Error;
use Neutrino\Error\Helper;
use Phalcon\Logger\Enum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class HelperTest extends TestCase
{
    /**
     * @return iterable<array{string, mixed}>
     */
    public static function verboseTypes(): iterable
    {
        yield ['null', null];
        yield ['array', []];
        yield ['123', 123];
        yield ['123.5', 123.5];
        yield ['true', true];
        yield ["'str'", 'str'];
        yield ["'abcdefgh...stuvwxyz'[26]", 'abcdefghijklmnopqrstuvwxyz'];
        yield ['object(stdClass)', new \stdClass()];
        yield ['object(RuntimeException)', new RuntimeException()];
        yield ['object(class@anonymous)', new class {}];
        yield ['StubSuit::Hearts', StubSuit::Hearts];
        yield ['array(object(stdClass))', [new \stdClass()]];
        yield ['array(object(stdClass), object(stdClass))', [new \stdClass(), new \stdClass()]];
        yield ['array.<stdClass>[4]', [new \stdClass(), new \stdClass(), new \stdClass(), new \stdClass()]];
        yield ['array(1, 2, 3)', [1, 2, 3]];
        yield ['array.<int>[6]', [1, 2, 3, 4, 5, 6]];
        yield ["array('a' => 1, 'b' => array)", ['a' => 1, 'b' => [2]]];
        yield ['array(object(stdClass), null, object(stdClass), 123)', [new \stdClass(), null, new \stdClass(), 123]];
        yield ['array[5]', [1, 'a', null, 2, 'b']];
    }

    #[DataProvider('verboseTypes')]
    public function testVerboseType(string $expected, mixed $value): void
    {
        $this->assertSame($expected, Helper::verboseType($value));
    }

    public function testVerboseTypeOfAPath(): void
    {
        $this->assertSame("'app/Http'", Helper::verboseType(BASE_PATH . '/app/Http'));
    }

    public function testVerboseTypeOfResources(): void
    {
        $resource = fopen('php://memory', 'r');
        $this->assertSame('resource', Helper::verboseType($resource));

        fclose($resource);
        $this->assertSame('resource (closed)', Helper::verboseType($resource));
    }

    /**
     * @return iterable<array{int|string, string, string, int}>
     */
    public static function types(): iterable
    {
        yield [-1, 'Uncaught exception', 'Uncaught exception', Enum::ERROR];
        yield [E_ERROR, 'E_ERROR', 'Fatal error [E_ERROR]', Enum::EMERGENCY];
        yield [E_WARNING, 'E_WARNING', 'Warning [E_WARNING]', Enum::WARNING];
        yield [E_PARSE, 'E_PARSE', 'Fatal error [E_PARSE]', Enum::CRITICAL];
        yield [E_NOTICE, 'E_NOTICE', 'Notice [E_NOTICE]', Enum::NOTICE];
        yield [E_CORE_ERROR, 'E_CORE_ERROR', 'Fatal error [E_CORE_ERROR]', Enum::EMERGENCY];
        yield [E_CORE_WARNING, 'E_CORE_WARNING', 'Warning [E_CORE_WARNING]', Enum::WARNING];
        yield [E_COMPILE_ERROR, 'E_COMPILE_ERROR', 'Fatal error [E_COMPILE_ERROR]', Enum::EMERGENCY];
        yield [E_COMPILE_WARNING, 'E_COMPILE_WARNING', 'Warning [E_COMPILE_WARNING]', Enum::WARNING];
        yield [E_USER_ERROR, 'E_USER_ERROR', 'Fatal error [E_USER_ERROR]', Enum::ERROR];
        yield [E_USER_WARNING, 'E_USER_WARNING', 'Warning [E_USER_WARNING]', Enum::WARNING];
        yield [E_USER_NOTICE, 'E_USER_NOTICE', 'Notice [E_USER_NOTICE]', Enum::NOTICE];
        yield [2048, 'E_STRICT', 'Deprecated [E_STRICT]', Enum::INFO];
        yield [E_RECOVERABLE_ERROR, 'E_RECOVERABLE_ERROR', 'Fatal error [E_RECOVERABLE_ERROR]', Enum::ERROR];
        yield [E_DEPRECATED, 'E_DEPRECATED', 'Deprecated [E_DEPRECATED]', Enum::INFO];
        yield [E_USER_DEPRECATED, 'E_USER_DEPRECATED', 'Deprecated [E_USER_DEPRECATED]', Enum::INFO];
        yield ['Other', '(unknown error bit Other)', '(unknown error bit Other)', Enum::ERROR];
    }

    #[DataProvider('types')]
    public function testTypes(int|string $code, string $type, string $verbose, int $level): void
    {
        $this->assertSame($type, Helper::getErrorType($code));
        $this->assertSame($verbose, Helper::verboseErrorType($code));
        $this->assertSame($level, Helper::getLogType($code));
    }

    public function testFormatError(): void
    {
        $this->assertSame(
            "E_WARNING\n  Message : msg\n in : /app/file.php(12)",
            Helper::format(Error::fromError(E_WARNING, 'msg', '/app/file.php', 12)),
        );
    }

    public function testFormatException(): void
    {
        $exception = self::exception();
        $line = $exception->getLine();

        $expected = "Uncaught exception\n  Class : RuntimeException\n  Code : 2\n  Message : outer\n in : " . __FILE__ . "($line)\n";

        foreach (Helper::formatExceptionTrace($exception) as $trace) {
            $expected .= "\n#" . $trace['id'] . ' ' . $trace['func'] . "\n" . str_repeat(' ', strlen((string) $trace['id']) + 2) . 'in : ' . $trace['where'];
        }

        $formatted = Helper::format(Error::fromException($exception));

        $this->assertStringStartsWith($expected, $formatted);
        $this->assertStringContainsString("\n\n# Previous exception : 1\n\nUncaught exception\n  Class : LogicException\n  Code : 1\n  Message : inner\n", $formatted);
    }

    public function testFormatExceptionTrace(): void
    {
        // The arguments are in the trace unless zend.exception_ignore_args (on in production).
        $previous = ini_set('zend.exception_ignore_args', '0');

        try {
            $traces = Helper::formatExceptionTrace(self::exception('arg', 12));
        } finally {
            ini_set('zend.exception_ignore_args', (string) $previous);
        }

        $this->assertSame(0, $traces[0]['id']);
        $this->assertSame(self::class . "->exception('arg', 12)", $traces[0]['func']);
        $this->assertSame(__FILE__, $traces[0]['file'] ?? null);
        $this->assertSame($traces[0]['file'] . '(' . ($traces[0]['line'] ?? 0) . ')', $traces[0]['where']);
    }

    private static function exception(string $arg = 'arg', int $number = 12): RuntimeException
    {
        return new RuntimeException('outer', 2, new \LogicException('inner', 1));
    }
}

enum StubSuit
{
    case Hearts;
}

<?php

declare(strict_types=1);

namespace Test\Micro;

use Fake\Kernels\Micro\StubKernelMicro;
use Neutrino\Micro\Middleware;
use Neutrino\Micro\MiddlewarePosition;
use Phalcon\Mvc\Micro;
use Phalcon\Mvc\Micro\Exception as MicroException;
use Test\TestCase\TestCase;

final class MicroKernelTest extends TestCase
{
    protected static function kernelClassInstance(): string
    {
        return MiddlewaresKernel::class;
    }

    protected function setUp(): void
    {
        RecordingMiddleware::$calls = [];
        RecordingMiddleware::$stop = false;

        parent::setUp();
    }

    public function testRoute(): void
    {
        $this->dispatch('get.test.abc');

        $this->assertSame('get.test.abc', $this->getContent());
    }

    public function testUnknownRoute(): void
    {
        // No notFound() handler.
        $this->expectException(MicroException::class);

        $this->dispatch('/unknown');
    }

    public function testMiddlewaresByPosition(): void
    {
        $this->dispatch('get.test.abc');

        $this->assertSame(['before', 'after', 'finish'], RecordingMiddleware::$calls);
    }

    public function testBeforeMiddlewareReturningFalseStopsTheRequest(): void
    {
        RecordingMiddleware::$stop = true;

        $this->dispatch('get.test.abc');

        $this->assertSame(['before'], RecordingMiddleware::$calls);
        $this->assertSame('', $this->getContent());
    }
}

abstract class RecordingMiddleware extends Middleware
{
    /** @var list<string> */
    public static array $calls = [];

    public static bool $stop = false;

    public function call(Micro $application): bool
    {
        self::$calls[] = strtolower($this->bindOn()->name);

        return !(self::$stop && $this->bindOn() === MiddlewarePosition::Before);
    }
}

final class BeforeMiddleware extends RecordingMiddleware
{
    public function bindOn(): MiddlewarePosition
    {
        return MiddlewarePosition::Before;
    }
}

final class AfterMiddleware extends RecordingMiddleware
{
    public function bindOn(): MiddlewarePosition
    {
        return MiddlewarePosition::After;
    }
}

final class FinishMiddleware extends RecordingMiddleware
{
    public function bindOn(): MiddlewarePosition
    {
        return MiddlewarePosition::Finish;
    }
}

final class MiddlewaresKernel extends StubKernelMicro
{
    protected array $middlewares = [
        BeforeMiddleware::class,
        AfterMiddleware::class,
        FinishMiddleware::class,
    ];
}

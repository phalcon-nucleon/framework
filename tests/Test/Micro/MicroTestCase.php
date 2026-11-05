<?php

declare(strict_types=1);

namespace Test\Micro;

use Fake\Kernels\Micro\StubKernelMicro;

trait MicroTestCase
{
    protected static function kernelClassInstance(): string
    {
        return StubKernelMicro::class;
    }
}

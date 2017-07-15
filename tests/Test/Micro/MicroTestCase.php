<?php

namespace Test\Micro;

use Fake\Kernels\Micro\StubKernelMicro;

trait MicroTestCase
{
    protected static function kernelClassInstance()
    {
        return StubKernelMicro::class;
    }
}
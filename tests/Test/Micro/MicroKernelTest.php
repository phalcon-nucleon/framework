<?php

namespace Test\Micro;

use Test\Stub\StubKernelMicro;
use Test\TestCase\TestCase;

class MicroKernelTest extends TestCase
{
    protected static function kernelClassInstance()
    {
        return StubKernelMicro::class;
    }

    public function testAny(){

    }
}
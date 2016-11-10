<?php

namespace Test\Middleware\Stub;

use Luxury\Foundation\Middleware\Disptacher;
use Luxury\Interfaces\Middleware\AfterInterface;
use Luxury\Interfaces\Middleware\BeforeInterface;
use Luxury\Interfaces\Middleware\FinishInterface;
use Luxury\Interfaces\Middleware\InitInterface;
use Test\Middleware\Middlewarize;
use Test\TestCase\TestListenable;
use Test\TestCase\TestListenize;

class DispatchMiddlewareStub extends Disptacher implements
    TestListenable,
    InitInterface,
    BeforeInterface,
    AfterInterface,
    FinishInterface
{
    use TestListenize, Middlewarize;
}

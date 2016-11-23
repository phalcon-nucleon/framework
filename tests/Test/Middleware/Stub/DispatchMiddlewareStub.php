<?php

namespace Test\Middleware\Stub;

use Neutrino\Foundation\Middleware\Disptacher;
use Neutrino\Interfaces\Middleware\AfterInterface;
use Neutrino\Interfaces\Middleware\BeforeInterface;
use Neutrino\Interfaces\Middleware\FinishInterface;
use Neutrino\Interfaces\Middleware\InitInterface;
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

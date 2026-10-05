<?php

declare(strict_types=1);

namespace Test\Middleware\Stub;

use Neutrino\Foundation\Middleware\Dispatcher;
use Neutrino\Interfaces\Middleware\AfterInterface;
use Neutrino\Interfaces\Middleware\BeforeInterface;
use Neutrino\Interfaces\Middleware\FinishInterface;
use Neutrino\Interfaces\Middleware\InitInterface;
use Test\Middleware\Middlewarize;
use Test\TestCase\TestListenable;
use Test\TestCase\TestListenize;

class DispatchMiddlewareStub extends Dispatcher implements
    TestListenable,
    InitInterface,
    BeforeInterface,
    AfterInterface,
    FinishInterface
{
    use TestListenize;
    use Middlewarize;
}

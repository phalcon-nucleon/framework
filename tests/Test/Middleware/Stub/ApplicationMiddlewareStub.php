<?php

namespace Test\Middleware\Stub;

use Luxury\Foundation\Middleware\Application;
use Luxury\Interfaces\Middleware\AfterInterface;
use Luxury\Interfaces\Middleware\BeforeInterface;
use Luxury\Interfaces\Middleware\FinishInterface;
use Luxury\Interfaces\Middleware\InitInterface;
use Test\Middleware\Middlewarize;
use Test\TestCase\TestListenable;
use Test\TestCase\TestListenize;

class ApplicationMiddlewareStub extends Application implements
    TestListenable,
    InitInterface,
    BeforeInterface,
    AfterInterface,
    FinishInterface
{
    use TestListenize, Middlewarize;
}

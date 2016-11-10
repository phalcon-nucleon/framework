<?php

namespace Test\Middleware\Stub;

use Luxury\Foundation\Middleware\Controller;
use Luxury\Interfaces\Middleware\AfterInterface;
use Luxury\Interfaces\Middleware\BeforeInterface;
use Luxury\Interfaces\Middleware\FinishInterface;
use Test\Middleware\Middlewarize;
use Test\TestCase\TestListenable;
use Test\TestCase\TestListenize;

class ControllerMiddlewareStub extends Controller implements
    TestListenable,
    BeforeInterface,
    AfterInterface,
    FinishInterface
{
    use TestListenize, Middlewarize;
}
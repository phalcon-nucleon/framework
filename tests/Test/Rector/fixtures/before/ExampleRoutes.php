<?php

namespace RectorFixture;

use Neutrino\Test\RoutesTestCase;

class ExampleRoutesTest extends RoutesTestCase
{
    protected static function kernelClassInstance()
    {
        return Kernel::class;
    }

    protected function routes()
    {
        return [$this->formatDataRoute('/', 'GET', true, 'index', 'index')];
    }
}

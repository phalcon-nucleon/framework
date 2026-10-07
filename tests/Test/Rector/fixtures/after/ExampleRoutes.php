<?php

namespace RectorFixture;

use Neutrino\Test\RoutesTestCase;

class ExampleRoutesTest extends RoutesTestCase
{
    protected static function kernelClassInstance(): string
    {
        return Kernel::class;
    }

    protected static function routes(): array
    {
        return [static::formatDataRoute('/', 'GET', true, 'index', 'index')];
    }
}

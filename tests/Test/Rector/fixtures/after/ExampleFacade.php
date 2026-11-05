<?php

namespace RectorFixture;

use Neutrino\Support\Facades\Facade;

class ExampleFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'example';
    }
}

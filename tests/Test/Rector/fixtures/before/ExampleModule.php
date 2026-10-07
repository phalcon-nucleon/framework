<?php

namespace RectorFixture;

use Neutrino\Module;
use Phalcon\DiInterface;

class ExampleModule extends Module
{
    protected $providers = [];

    public function initialise(DiInterface $di)
    {
    }
}

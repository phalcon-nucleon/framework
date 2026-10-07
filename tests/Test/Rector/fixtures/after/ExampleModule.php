<?php

namespace RectorFixture;

use Neutrino\Module;

class ExampleModule extends Module
{
    protected array $providers = [];

    public function initialise(\Phalcon\Di\DiInterface $di): void
    {
    }
}

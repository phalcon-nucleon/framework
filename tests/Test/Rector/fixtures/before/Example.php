<?php

namespace RectorFixture;

use Neutrino\Support\SimpleProvider;

class Example extends SimpleProvider
{
    protected $name = 'example';

    protected $shared = true;

    protected $class = \stdClass::class;

    protected $options;
}

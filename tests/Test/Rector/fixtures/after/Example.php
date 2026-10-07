<?php

namespace RectorFixture;

use Neutrino\Support\SimpleProvider;

class Example extends SimpleProvider
{
    protected string $name = 'example';

    protected bool $shared = true;

    protected string $class = \stdClass::class;

    protected array $options = [];
}

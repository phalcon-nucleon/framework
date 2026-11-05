<?php

declare(strict_types=1);

namespace Fake\Core;

use Neutrino\Support\DesignPatterns\Singleton;

class StubSingleton extends Singleton
{
    protected string $var;

    protected function __construct()
    {
        parent::__construct();

        $this->var = 'test';
    }

    public function getVar(): string
    {
        return $this->var;
    }
}

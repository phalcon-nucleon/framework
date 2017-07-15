<?php

namespace Fake\Core;

use Neutrino\Support\DesignPatterns\Singleton;

class StubSingleton extends Singleton
{
    protected $var;

    protected function __construct()
    {
        parent::__construct();

        $this->var = 'test';
    }

    public function getVar()
    {
        return $this->var;
    }
}
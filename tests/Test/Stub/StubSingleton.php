<?php

namespace Test\Stub;

use Luxury\Support\DesignPatterns\Singleton;

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
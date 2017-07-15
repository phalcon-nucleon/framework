<?php

namespace Neutrino\Database;

use Neutrino\Constants\Services;
use Neutrino\Support\DesignPatterns\Strategy;

class DatabaseStrategy extends Strategy
{
    use Strategy\MagicCallStrategyTrait;

    /**
     * CacheStrategy constructor.
     */
    public function __construct()
    {
        $database = $this->{Services::CONFIG}->database;

        $this->default = $database->default;

        $this->supported = array_keys((array)$database->connections);
    }

    /**
     * @inheritdoc
     */
    protected function make($use)
    {
        return $this->{Services::DB . '.' . $use};
    }
}
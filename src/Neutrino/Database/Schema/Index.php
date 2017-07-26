<?php

namespace Neutrino\Database\Schema;

use Neutrino\Support\Fluent\Fluentable;
use Neutrino\Support\Fluent\Fluentize;
use Phalcon\Db\Index as DbIndex;

class Index extends DbIndex /*implements Fluentable*/
{
/*
    use Fluentize {
        __construct as fluentConstruct;
    }

    public function __construct($attributes = [])
    {
        parent::__construct($attributes);
    }
*/
}
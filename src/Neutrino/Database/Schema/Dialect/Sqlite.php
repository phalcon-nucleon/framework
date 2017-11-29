<?php

namespace Neutrino\Database\Schema\Dialect;

use Neutrino\Database\Schema;
use Neutrino\Support\Fluent;
use Phalcon\Db\Dialect;

/**
 * Class Mysql
 *
 * @package Neutrino\Database\Schema\Dialect
 */
class Sqlite extends Dialect\Sqlite implements Schema\DialectInterface
{
    use WrapperTrait, SqliteTrait;
}

<?php

namespace Neutrino\Database\Schema\Dialect;

use Neutrino\Database\Schema;
use Neutrino\Support\Fluent;
use Phalcon\Db\Column;
use Phalcon\Db\Dialect;

/**
 * Class Mysql
 *
 * @package Neutrino\Database\Schema\Dialect
 */
class Postgresql extends Dialect\Postgresql implements Schema\DialectInterface
{
    use WrapperTrait, PostgresqlTrait;
}

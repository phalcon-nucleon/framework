<?php

namespace Neutrino\Database\Schema\Dialect;

use Neutrino\Database\Schema;
use Phalcon\Db\Dialect;

/**
 * Class Mysql
 *
 * @package Neutrino\Database\Schema\Dialect
 */
class Mysql extends Dialect\Mysql implements Schema\DialectInterface
{
    use WrapperTrait, MysqlTrait;
}

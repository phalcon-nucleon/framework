<?php

namespace Neutrino\Database\Schema\Dialect;

use Phalcon\Db\Dialect as DbDialect;
use Phalcon\Db\DialectInterface as DbDialectInterface;
use Neutrino\Database\Schema\Dialect as SchemaDialect;
use Neutrino\Database\Schema\DialectInterface as SchemaDialectInterface;

/**
 * Class Factory
 *
 * @package Neutrino\Database\Schema\Dialect
 */
final class Factory
{
    /**
     * @param \Phalcon\Db\DialectInterface $dialect
     *
     * @return \Neutrino\Database\Schema\DialectInterface
     */
    public static function create(DbDialectInterface $dialect)
    {
        if ($dialect instanceof SchemaDialectInterface) {
            return $dialect;
        } elseif ($dialect instanceof DbDialect\Mysql) {
            return new SchemaDialect\Mysql($dialect);
        } elseif ($dialect instanceof DbDialect\Postgresql) {
            return new SchemaDialect\Postgresql($dialect);
        } elseif ($dialect instanceof DbDialect\Sqlite) {
            return new SchemaDialect\Sqlite($dialect);
        } else {
            return new SchemaDialect\Wrapper($dialect);
        }
    }
}

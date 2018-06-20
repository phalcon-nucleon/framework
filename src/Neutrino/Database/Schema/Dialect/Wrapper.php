<?php

namespace Neutrino\Database\Schema\Dialect;

use Neutrino\Database\Schema;
use Phalcon\Db;

/**
 * Class Wrapper
 *
 * @package Neutrino\Database\Schema\Dialect
 */
class Wrapper extends Db\Dialect implements Schema\DialectInterface
{
    use WrapperTrait, Schema\DialectTrait;

    /**
     * Get SQL Enable foreign key constraints.
     *
     * @return string
     * @throws \RuntimeException
     */
    public function enableForeignKeyConstraints()
    {
        throw new \RuntimeException(self::class . ' doesn\'t support ' . __FUNCTION__);
    }

    /**
     * Get SQL Disable foreign key constraints.
     *
     * @return string
     * @throws \RuntimeException
     */
    public function disableForeignKeyConstraints()
    {
        throw new \RuntimeException(self::class . ' doesn\'t support ' . __FUNCTION__);
    }

    /**
     * Generates SQL for rename table
     *
     * @param string      $from
     * @param string      $to
     * @param null|string $schema
     *
     * @return string
     */
    public function renameTable($from, $to, $schema = null)
    {
        throw new \RuntimeException(self::class . ' doesn\'t support ' . __FUNCTION__);
    }
}

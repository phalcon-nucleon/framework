<?php

namespace Neutrino\Database\Migrations\Storage;

use Neutrino\Database\Migrations\Storage\Database\MigrationModel;
use Neutrino\Database\Schema\Blueprint;
use Neutrino\Database\Schema\Builder;
use Neutrino\Repositories\Repository;

/**
 * Class DatabaseRepository
 *
 * @package Neutrino\Database\Migrations
 */
class DatabaseStorage extends Repository implements StorageInterface
{
    protected $table = 'migrations';

    /**
     * Get list of migrations.
     *
     * @return array
     */
    public function getMigrations()
    {
        return $this->all();
    }

    /**
     * Get the last migration batch.
     *
     * @return array
     */
    public function getLast()
    {
        return $this->first([], [
            'migration' => 'asc'
        ])->toArray();
    }

    /**
     * Log that a migration was run.
     *
     * @param string $migration Migration Name
     * @param int    $batch     Batch number
     *
     * @throws \Exception
     * @return void
     */
    public function log($migration, $batch)
    {
        $migration = new MigrationModel([
            'migration' => $migration,
            'batch'     => $batch,
            'migrate_at' => time()
        ]);

        if (!$this->create($migration)) {
            $messages = array_map(function ($message) {
                return (string)$message;
            }, $this->getMessages());

            throw new \Exception(implode(PHP_EOL, $messages));
        }
    }

    /**
     * Remove a migration from the log.
     *
     * @param string $migration Migration Name
     *
     * @throws \Exception
     * @return void
     */
    public function remove($migration)
    {
        $migration = $this->first(['migration' => $migration]);

        if (!$this->delete($migration)) {
            $messages = array_map(function ($message) {
                return (string)$message;
            }, $this->getMessages());

            throw new \Exception(implode(PHP_EOL, $messages));
        }
    }

    /**
     * @return int
     */
    public function getLastBatchNumber()
    {
        $result = $this
            ->db
            ->query('SELECT MAX(bacth) FROM ' . MigrationModel::class)
            ->fetch();

        return (int)$result;
    }

    /**
     * @return int
     */
    public function getNextBatchNumber()
    {
        return $this->getLastBatchNumber() + 1;
    }

    /**
     * @return bool
     */
    public function createStorage()
    {
        (new Builder())->create($this->table, function (Blueprint $blueprint) {
            $blueprint->increments('id')->primary();
            $blueprint->string('migration', 256);
            $blueprint->integer('batch');
        });

        return true;
    }

    /**
     * @return bool
     */
    public function storageExist()
    {
        return $this->db->tableExists($this->table);
    }
}

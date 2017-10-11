<?php

namespace Neutrino\Database\Migrations\Storage;

use Neutrino\Constants\Services;
use Neutrino\Database\Migrations\Storage\Database\MigrationModel;
use Neutrino\Database\Schema\Blueprint;
use Neutrino\Database\Schema\Builder;
use Phalcon\Di;

/**
 * Class DatabaseRepository
 *
 * @package Neutrino\Database\Migrations
 */
class DatabaseStorage implements StorageInterface
{
    protected $table = 'migrations';

    /**
     * Get the ran migrations.
     *
     * @return array
     */
    public function getRan()
    {
        return MigrationModel::find([
            'order' => 'batch ASC, migration ASC',
        ])->toArray();
    }

    /**
     * Get list of migrations.
     *
     * @param int $steps
     *
     * @return array
     */
    public function getMigrations($steps)
    {
        return MigrationModel::find([
            'batch >= 1',
            'order' => 'batch DESC, migration DESC',
            'limit' => $steps
        ])->toArray();
    }

    /**
     * Get the last migration batch.
     *
     * @return array
     */
    public function getLast()
    {
        return MigrationModel::find([
            'order' => 'migration DESC',
            'limit' => 1
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
            'batch'     => $batch
        ]);

        if (!$migration->save()) {
            $messages = array_map(function ($message) {
                return (string)$message;
            }, $migration->getMessages());

            throw new \Exception(implode(PHP_EOL, $messages));
        }
    }

    public function delete($migration)
    {
        $migration = MigrationModel::find([
            'migration = :migration:',
            'bind' => [
                'migration' => $migration
            ]
        ])->getFirst();

        if (!$migration->delete()) {
            $messages = array_map(function ($message) {
                return (string)$message;
            }, $migration->getMessages());

            throw new \Exception(implode(PHP_EOL, $messages));
        }
    }

    /**
     * @return int
     */
    public function getLastBatchNumber()
    {
        return (int)MigrationModel::maximum([
            'column' => 'batch'
        ]);
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
        return Di::getDefault()->get(Services::DB)->tableExists($this->table);
    }
}

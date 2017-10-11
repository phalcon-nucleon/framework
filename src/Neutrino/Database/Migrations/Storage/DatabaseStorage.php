<?php

namespace Neutrino\Database\Migrations\Storage;

use Neutrino\Constants\Services;
use Neutrino\Database\Migrations\Storage\Database\MigrationModel;
use Neutrino\Database\Migrations\Storage\Database\MigrationRepository;
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
     * @var \Neutrino\Repositories\Repository
     */
    protected $repository;

    public function __construct()
    {
        $this->repository = Di::getDefault()->get(MigrationRepository::class);
    }

    /**
     * Get the ran migrations.
     *
     * @return array
     */
    public function getRan()
    {
        return $this->repository->find([], [
            'batch'     => 'ASC',
            'migration' => 'ASC'
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
        return $this->repository->find(
            [
                'batch' => [
                    'operator' => '>=',
                    'value'    => 1
                ]
            ],
            [
                'batch'     => 'DESC',
                'migration' => 'DESC'
            ],
            $steps
        )->toArray();
    }

    /**
     * Get the last migration batch.
     *
     * @return array
     */
    public function getLast()
    {
        return $this->repository->first([], ['migration' => 'DESC'])->toArray();
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

        if (!$this->repository->create($migration)) {
            $messages = array_map(function ($message) {
                return (string)$message;
            }, $this->repository->getMessages());

            throw new \Exception(implode(PHP_EOL, $messages));
        }
    }

    public function delete($migration)
    {
        $migration = $this->repository->first(['migration' => $migration]);

        if (!$this->repository->delete($migration)) {
            $messages = array_map(function ($message) {
                return (string)$message;
            }, $this->repository->getMessages());

            throw new \Exception(implode(PHP_EOL, $messages));
        }
    }

    /**
     * @return int
     */
    public function getLastBatchNumber()
    {
        return (int)$this->repository->maximum('batch');
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
            $blueprint->integer('batch')->unsigned();
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

<?php

namespace Neutrino\Database\Migrations\Storage;

/**
 * Interface StorageInterface
 *
 * @package Neutrino\Database\Migrations\Storage
 */
interface StorageInterface
{
    /**
     * Get the ran migrations.
     *
     * @return array
     */
    public function getRan();

    /**
     * Get list of migrations.
     *
     * @param  int  $steps
     * @return array
     */
    public function getMigrations($steps);

    /**
     * Get the last migration batch.
     *
     * @return array
     */
    public function getLast();

    /**
     * Log that a migration was run.
     *
     * @param string $migration Migration Name
     * @param int    $batch     Batch number
     *
     * @throws \Exception
     * @return void
     */
    public function log($migration, $batch);

    /**
     * Remove a migration from the log.
     *
     * @param string $migration Migration Name
     *
     * @throws \Exception
     * @return void
     */
    public function delete($migration);

    /**
     * @return int
     */
    public function getLastBatchNumber();

    /**
     * @return int
     */
    public function getNextBatchNumber();

    /**
     * @return bool
     */
    public function createStorage();

    /**
     * @return bool
     */
    public function storageExist();
}

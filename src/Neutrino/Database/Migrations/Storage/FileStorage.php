<?php

namespace Neutrino\Database\Migrations\Storage;

/**
 * Class FileStorage
 *
 * @package Neutrino\Database\Migrations\Storage
 */
class FileStorage implements StorageInterface
{
    private $data;

    /**
     * Get list of migrations.
     *
     * @return array
     */
    public function getMigrations()
    {
        return $this->data();
    }

    /**
     * Get the last migration batch.
     *
     * @return array
     */
    public function getLast()
    {
        $data = $this->data();

        $migrate_at = array_column($data, 'migration');

        arsort($migrate_at);

        reset($migrate_at);

        return $data[key($migrate_at)];
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
        $data = $this->data();

        $data[] = ['migration' => $migration, 'batch' => $batch];

        $this->data = $data;
    }

    /**
     * Remove a migration from the log.
     *
     * @param string $migration Migration Name
     *
     * @throws \Exception
     * @return void
     */
    public function delete($migration)
    {
        $data = $this->data();

        foreach ($data as $key => $datum) {
            if($datum['migration'] == $migration){
                unset($data[$key]);
            }
        }

        $this->data = $data;
    }

    /**
     * @return int
     */
    public function getLastBatchNumber()
    {
        $data = $this->data();

        $batch = array_column($data, 'batch');

        rsort($batch);

        reset($batch);

        return $batch[key($batch)];
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
        if ($this->storageExist()) {
            return true;
        }

        return file_put_contents($this->getFilePath(), '[]');
    }

    /**
     * @return bool
     */
    public function storageExist()
    {
        return file_exists($this->getFilePath());
    }

    private function getFilePath()
    {
        return BASE_PATH . '/migrations/.migrations.dat';
    }

    private function data()
    {
        if (!isset($this->data)) {
            $this->data = (array)json_decode(file_get_contents($this->getFilePath()), true);
        }

        return $this->data;
    }
}

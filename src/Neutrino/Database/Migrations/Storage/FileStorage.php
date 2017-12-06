<?php

namespace Neutrino\Database\Migrations\Storage;

use Neutrino\Support\Arr;

/**
 * Class FileStorage
 *
 * @package Neutrino\Database\Migrations\Storage
 */
class FileStorage implements StorageInterface
{
    private $data;

    /**
     * Get the ran migrations.
     *
     * @return array
     */
    public function getRan()
    {
        $data = $this->getData();

        array_multisort(
            array_column($data, 'batch'), SORT_ASC,
            array_column($data, 'migration'), SORT_ASC,
            $data
        );

        return Arr::pluck($data, 'migration');
    }

    /**
     * Get list of migrations.
     *
     * @param  int $steps
     *
     * @return array
     */
    public function getMigrations($steps)
    {
        $data = $this->getData();

        $data = array_filter($data, function ($datum) {
            return (int)$datum['batch'] >= 1;
        });

        array_multisort(
            array_column($data, 'batch'), SORT_DESC,
            array_column($data, 'migration'), SORT_DESC,
            $data
        );

        return array_slice($data, 0, $steps);
    }

    /**
     * Get the last migration batch.
     *
     * @return array
     */
    public function getLast()
    {
        $data = $this->getData();

        if(empty($data)){
            return [];
        }

        $lastBatch = $this->getLastBatchNumber();

        $data = array_filter($data, function ($datum) use ($lastBatch) {
            return (int)$datum['batch'] = $lastBatch;
        });

        $migrate_at = array_column($data, 'migration');

        arsort($migrate_at);

        reset($migrate_at);

        return [$data[key($migrate_at)]];
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
        $data = $this->getData();

        $data[] = ['migration' => $migration, 'batch' => $batch];

        $this->setData($data);
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
        $data = $this->getData();

        foreach ($data as $key => $datum) {
            if ($datum['migration'] == $migration) {
                unset($data[$key]);
            }
        }

        $this->setData($data);
    }

    /**
     * @return int
     */
    public function getLastBatchNumber()
    {
        $data = $this->getData();

        if(empty($data)){
            return 0;
        }

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

        return file_put_contents($this->getFilePath(), '[]') === 2;
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

    private function getData()
    {
        if (!isset($this->data)) {
            if ($this->storageExist()) {
                $this->data = (array)json_decode(file_get_contents($this->getFilePath()), true);
            } else {
                $this->data = [];
            }
        }

        return $this->data;
    }

    private function setData($data)
    {
        $this->data = $data;

        file_put_contents($this->getFilePath(), json_encode($data));
    }
}

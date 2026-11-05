<?php

declare(strict_types=1);

namespace Neutrino\Database\Migrations\Storage;

use Neutrino\Database\Migrations\Storage\Database\MigrationModel;
use Neutrino\Database\Migrations\Storage\Database\MigrationRepository;
use Neutrino\Database\Schema\Blueprint;
use Neutrino\Database\Schema\Builder;
use Phalcon\Db\Adapter\AdapterInterface;
use Phalcon\Di\Di;
use Phalcon\Di\DiInterface;
use Phalcon\Mvc\Model\ResultsetInterface;
use RuntimeException;

/**
 * The `migrations` table (`id`, `migration`, `batch`), on the `migrations.connection` connection (the default
 * one otherwise).
 *
 * @phpstan-import-type MigrationRow from StorageInterface
 */
class DatabaseStorage implements StorageInterface
{
    protected string $table = 'migrations';

    protected MigrationRepository $repository;

    protected DiInterface $di;

    public function __construct(?DiInterface $di = null, ?MigrationRepository $repository = null)
    {
        $this->di = $di ?? Di::getDefault() ?? throw new RuntimeException(static::class . ': no container.');
        $this->repository = $repository ?? new MigrationRepository();
        $this->repository->setDI($this->di);
    }

    public function getRan(): array
    {
        return array_column($this->rows($this->repository->find([], ['batch' => 'ASC', 'migration' => 'ASC'])), 'migration');
    }

    public function getMigrations(int $steps): array
    {
        return $this->rows($this->repository->find(['batch' => ['operator' => '>=', 'value' => 1]], ['batch' => 'DESC', 'migration' => 'DESC'], $steps));
    }

    public function getLast(): array
    {
        return $this->rows($this->repository->find(['batch' => $this->getLastBatchNumber()], ['migration' => 'DESC']));
    }

    public function log(string $migration, int $batch): void
    {
        $model = new MigrationModel();
        $model->migration = $migration;
        $model->batch = $batch;

        // No transaction of the transaction manager: the log joins the transaction of the migration, if any.
        if (!$this->repository->create($model, false)) {
            throw new RuntimeException("Migration \"$migration\" not logged: " . $this->messages());
        }
    }

    public function delete(string $migration): void
    {
        $model = $this->repository->first(['migration' => $migration]);

        if ($model !== null && !$this->repository->delete($model, false)) {
            throw new RuntimeException("Migration \"$migration\" not removed from the log: " . $this->messages());
        }
    }

    public function getLastBatchNumber(): int
    {
        $max = $this->repository->maximum('batch');

        return is_numeric($max) ? (int) $max : 0;
    }

    public function getNextBatchNumber(): int
    {
        return $this->getLastBatchNumber() + 1;
    }

    public function createStorage(): bool
    {
        (new Builder($this->connection()))->create($this->table, function (Blueprint $table): void {
            $table->increments('id');
            $table->string('migration');
            $table->integer('batch');
        });

        return true;
    }

    public function storageExist(): bool
    {
        return $this->connection()->tableExists($this->table);
    }

    /**
     * The connection of the table.
     */
    public function connection(): AdapterInterface
    {
        return (new MigrationModel())->getWriteConnection();
    }

    /**
     * @return list<MigrationRow>
     */
    private function rows(ResultsetInterface $resultset): array
    {
        $rows = [];

        foreach ($resultset->toArray() as $row) {
            if (is_array($row) && is_scalar($row['migration'] ?? null) && is_numeric($row['batch'] ?? null)) {
                $rows[] = ['migration' => (string) $row['migration'], 'batch' => (int) $row['batch']];
            }
        }

        return $rows;
    }

    private function messages(): string
    {
        return implode(PHP_EOL, array_map(strval(...), $this->repository->getMessages()));
    }
}

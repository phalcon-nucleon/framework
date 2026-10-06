<?php

declare(strict_types=1);

namespace Neutrino\Database\Migrations\Storage;

/**
 * The migrations that ran, and their batch.
 *
 * @phpstan-type MigrationRow array{migration: string, batch: int}
 */
interface StorageInterface
{
    /**
     * The migrations that ran, in their order.
     *
     * @return list<string>
     */
    public function getRan(): array;

    /**
     * The last `$steps` migrations that ran, the last one first.
     *
     * @return list<MigrationRow>
     */
    public function getMigrations(int $steps): array;

    /**
     * The migrations of the last batch, the last one first.
     *
     * @return list<MigrationRow>
     */
    public function getLast(): array;

    /**
     * Log that a migration was run.
     */
    public function log(string $migration, int $batch): void;

    /**
     * Remove a migration from the log.
     */
    public function delete(string $migration): void;

    public function getLastBatchNumber(): int;

    public function getNextBatchNumber(): int;

    public function createStorage(): bool;

    public function storageExist(): bool;
}

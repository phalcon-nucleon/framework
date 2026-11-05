<?php

declare(strict_types=1);

namespace Neutrino\Interfaces\Repositories;

use Generator;
use Phalcon\Messages\MessageInterface;
use Phalcon\Mvc\Model\ResultsetInterface;
use Phalcon\Mvc\ModelInterface;

/**
 * Queries and writes of a model.
 *
 * `$params` are conditions on the model attributes, joined with AND:
 * - `'name' => 'Nucleon'`: `name = 'Nucleon'` (any scalar), `'name' => null`: `name IS NULL`;
 * - `'id' => [1, 2]`: `id IN (1, 2)`;
 * - `'name' => ['operator' => 'LIKE', 'value' => 'N%']`, with `=`, `!=`, `<>`, `<`, `<=`, `>`, `>=`, `LIKE`,
 *   `NOT LIKE`, `IN`, `NOT IN`, `IS NULL`, `IS NOT NULL`.
 *
 * `$order`: `['name', 'id' => 'DESC']`. Unknown attributes, operators and directions throw an
 * `\InvalidArgumentException`: the values are bound, the names never reach the query unchecked.
 */
interface RepositoryInterface
{
    /**
     */
    public function all(): ResultsetInterface;

    /**
     * @param array<string, mixed>|null $params
     */
    public function count(?array $params = null): int;

    /**
     * @param array<string, mixed>   $params
     * @param array<int|string, string>|null $order
     *
     * @return ResultsetInterface
     */
    public function find(array $params = [], ?array $order = null, ?int $limit = null, ?int $offset = null): ResultsetInterface;

    /**
     * @param array<string, mixed>   $params
     * @param array<int|string, string>|null $order
     *
     * @return ModelInterface<mixed>|null
     */
    public function first(array $params = [], ?array $order = null): ?ModelInterface;

    /**
     * The first model matching `$params`, or a new one filled with them (created when `$create`).
     *
     * @param array<string, mixed> $params
     *
     * @return ModelInterface<mixed>
     */
    public function firstOrNew(array $params = [], bool $create = false, bool $withTransaction = false): ModelInterface;

    /**
     * @param array<string, mixed> $params
     *
     * @return ModelInterface<mixed>
     */
    public function firstOrCreate(array $params = [], bool $withTransaction = false): ModelInterface;

    /**
     * @param ModelInterface<mixed>|list<ModelInterface<mixed>> $value
     */
    public function create(ModelInterface|array $value, bool $withTransaction = true): bool;

    /**
     * @param ModelInterface<mixed>|list<ModelInterface<mixed>> $value
     */
    public function save(ModelInterface|array $value, bool $withTransaction = true): bool;

    /**
     * @param ModelInterface<mixed>|list<ModelInterface<mixed>> $value
     */
    public function update(ModelInterface|array $value, bool $withTransaction = true): bool;

    /**
     * @param ModelInterface<mixed>|list<ModelInterface<mixed>> $value
     */
    public function delete(ModelInterface|array $value, bool $withTransaction = true): bool;

    /**
     * Messages of the last failed write.
     *
     * @return list<MessageInterface|string>
     */
    public function getMessages(): array;

    /**
     * Iterates over the models matching `$params`, `$pad` rows per query, from the row `$start` to `$end`.
     *
     * @param array<string, mixed>   $params
     * @param array<int|string, string>|null $order
     *
     * @return Generator<int, ModelInterface<mixed>>
     */
    public function each(array $params = [], ?int $start = null, ?int $end = null, int $pad = 100, ?array $order = null): Generator;
}

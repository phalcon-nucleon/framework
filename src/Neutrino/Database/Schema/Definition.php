<?php

declare(strict_types=1);

namespace Neutrino\Database\Schema;

use Neutrino\Support\Fluent;

/**
 * A column, an index, a foreign key or a command of a {@see Blueprint}. Each modifier sets the attribute of its name.
 *
 * Columns:
 * @method $this default(mixed $value)
 * @method $this nullable(bool $nullable = true)
 * @method $this unsigned(bool $unsigned = true)
 * @method $this autoIncrement(bool $autoIncrement = true)
 * @method $this size(int $size)
 * @method $this scale(int $scale)
 * @method $this precision(int $precision) Fractional seconds of a date or time column
 * @method $this comment(string $comment)
 * @method $this first()
 * @method $this after(string $column)
 * @method $this onUpdate(string $expression) MySQL: `ON UPDATE` of a `DATETIME` or `TIMESTAMP` column (or the foreign key's action)
 *
 * Indexes:
 * @method $this primary(bool|string $name = true)
 * @method $this unique(bool|string $name = true)
 * @method $this index(bool|string $name = true)
 *
 * Foreign keys:
 * @method $this foreign()
 * @method $this on(string $table)
 * @method $this references(string|list<string> $columns)
 * @method $this onDelete(string $action)
 */
class Definition extends Fluent {}

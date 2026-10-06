<?php

declare(strict_types=1);

namespace Neutrino\Database\Schema\Grammar;

use InvalidArgumentException;
use Neutrino\Database\Schema\Definition;
use Neutrino\Database\Schema\Grammar;
use Phalcon\Db\Column;
use Phalcon\Db\ColumnInterface;
use Phalcon\Db\DialectInterface;

/**
 * Column types on the native types of Phalcon 5 (`Db\Column::TYPE_*`). Each database overrides the types its
 * Phalcon dialect does not know.
 *
 * A `type<Name>()` method returns the type part of the Phalcon column definition (`type`, `typeReference`,
 * `size`, `scale`…) of a {@see Definition} of type `<name>`. It can also set the other attributes of the column
 * (`default`: `null` once the default is written in the type).
 */
abstract class BaseGrammar implements Grammar
{
    /**
     * Attributes of a {@see Definition} passed to the Phalcon column as they are.
     */
    private const array COLUMN_OPTIONS = ['unsigned', 'autoIncrement', 'primary', 'first', 'after', 'comment'];

    /**
     * Quotes an identifier.
     */
    abstract protected function wrap(string $name): string;

    /**
     * The Phalcon dialect of the database.
     */
    abstract protected function dialect(): DialectInterface;

    public function column(Definition $column): ColumnInterface
    {
        $name = self::string($column->get('name'));
        $type = self::string($column->get('type'));
        $method = 'type' . ucfirst($type);

        if ($type === '' || !method_exists($this, $method)) {
            throw new InvalidArgumentException("Column \"$name\": unknown type \"$type\".");
        }

        /** @var array<string, mixed> $definition */
        $definition = $this->{$method}($column);
        $definition['notNull'] = !$column->get('nullable', false);

        foreach (self::COLUMN_OPTIONS as $option) {
            if (!array_key_exists($option, $definition) && $column->get($option) !== null) {
                $definition[$option] = $column->get($option);
            }
        }

        if (!array_key_exists('default', $definition) && $column->get('default') !== null) {
            $definition['default'] = $this->defaultValue($column->get('default'));
        }

        // @phpstan-ignore argument.type (the definition is checked by Phalcon)
        return new Column($name, $definition);
    }

    public function renameTable(string $from, string $to, string $schema = ''): string
    {
        return 'ALTER TABLE ' . $this->wrapTable($from, $schema) . ' RENAME TO ' . $this->wrap($to);
    }

    public function renameColumn(string $table, string $from, string $to, string $schema = ''): string
    {
        return 'ALTER TABLE ' . $this->wrapTable($table, $schema) . ' RENAME COLUMN ' . $this->wrap($from) . ' TO ' . $this->wrap($to);
    }

    public function addColumn(string $table, ColumnInterface $column, string $schema = ''): string
    {
        $sql = $this->dialect()->addColumn($table, $schema, $column);

        return $column->isPrimary() ? $sql . '; ' . $this->addPrimary($table, [$column->getName()], $schema) : $sql;
    }

    public function modifyColumn(string $table, ColumnInterface $column, ColumnInterface $current, string $schema = ''): ?string
    {
        return null;
    }

    public function addPrimary(string $table, array $columns, string $schema = ''): string
    {
        return 'ALTER TABLE ' . $this->wrapTable($table, $schema) . ' ADD PRIMARY KEY (' . $this->columnize($columns) . ')';
    }

    public function dropPrimary(string $table, string $schema = ''): string
    {
        return 'ALTER TABLE ' . $this->wrapTable($table, $schema) . ' DROP PRIMARY KEY';
    }

    public function dropTables(array $tables, string $schema = ''): array
    {
        return array_map(fn(string $table): string => 'DROP TABLE IF EXISTS ' . $this->wrapTable($table, $schema), $tables);
    }

    public function isSystemTable(string $table): bool
    {
        return false;
    }

    public function primaryAsIndex(): bool
    {
        return true;
    }

    public function supportsSchemaTransactions(): bool
    {
        return true;
    }

    protected function wrapTable(string $table, string $schema = ''): string
    {
        return ($schema === '' ? '' : $this->wrap($schema) . '.') . $this->wrap($table);
    }

    /**
     * @param list<string> $columns
     */
    protected function columnize(array $columns): string
    {
        return implode(', ', array_map($this->wrap(...), $columns));
    }

    /**
     * The default of a column, as Phalcon writes it.
     */
    protected function defaultValue(mixed $value): mixed
    {
        // Phalcon writes false as ''.
        return is_bool($value) ? (int) $value : $value;
    }

    /**
     * Fractional seconds precision of a date or time column (0: none).
     */
    protected function precision(Definition $column): int
    {
        $precision = $column->get('precision');

        return is_int($precision) && $precision > 0 ? $precision : 0;
    }

    /**
     * `VARCHAR(n) CHECK ("column" IN ('a', 'b'))`, for the databases without an enum type.
     *
     * @return array<string, mixed>
     */
    protected function checkedEnum(Definition $column): array
    {
        $values = array_map(self::string(...), (array) $column->get('values', []));
        $length = max(1, ...array_map(mb_strlen(...), $values ?: ['']));
        $quoted = array_map(static fn(string $value): string => "'" . str_replace("'", "''", $value) . "'", $values);

        return [
            'type'          => "VARCHAR($length) CHECK (" . $this->wrap(self::string($column->get('name'))) . ' IN (' . implode(', ', $quoted) . '))',
            'typeReference' => Column::TYPE_TEXT,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function sized(int $type, Definition $column, ?int $default = null): array
    {
        $size = $column->get('size') ?? $default;

        return $size === null ? ['type' => $type] : ['type' => $type, 'size' => $size];
    }

    /**
     * @return array<string, mixed>
     */
    protected function numeric(int $type, Definition $column): array
    {
        $definition = ['type' => $type];

        foreach (['size', 'scale'] as $key) {
            if ($column->get($key) !== null) {
                $definition[$key] = $column->get($key);
            }
        }

        return $definition;
    }

    /**
     * @return array<string, mixed>
     */
    protected function temporal(int $type, Definition $column): array
    {
        $precision = $this->precision($column);

        return $precision > 0 ? ['type' => $type, 'size' => $precision] : ['type' => $type];
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeBoolean(Definition $column): array
    {
        return ['type' => Column::TYPE_BOOLEAN];
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeTinyInteger(Definition $column): array
    {
        return ['type' => Column::TYPE_TINYINTEGER];
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeSmallInteger(Definition $column): array
    {
        return ['type' => Column::TYPE_SMALLINTEGER];
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeMediumInteger(Definition $column): array
    {
        return ['type' => Column::TYPE_MEDIUMINTEGER];
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeInteger(Definition $column): array
    {
        return ['type' => Column::TYPE_INTEGER];
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeBigInteger(Definition $column): array
    {
        return ['type' => Column::TYPE_BIGINTEGER];
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeDecimal(Definition $column): array
    {
        return ['type' => Column::TYPE_DECIMAL, 'size' => $column->get('size') ?? 10, 'scale' => $column->get('scale') ?? 0];
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeDouble(Definition $column): array
    {
        return $this->numeric(Column::TYPE_DOUBLE, $column);
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeFloat(Definition $column): array
    {
        return $this->numeric(Column::TYPE_FLOAT, $column);
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeJson(Definition $column): array
    {
        return ['type' => Column::TYPE_JSON];
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeJsonb(Definition $column): array
    {
        return ['type' => Column::TYPE_JSONB];
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeChar(Definition $column): array
    {
        return $this->sized(Column::TYPE_CHAR, $column);
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeString(Definition $column): array
    {
        return $this->sized(Column::TYPE_VARCHAR, $column);
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeText(Definition $column): array
    {
        return ['type' => Column::TYPE_TEXT];
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeMediumText(Definition $column): array
    {
        return ['type' => Column::TYPE_MEDIUMTEXT];
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeLongText(Definition $column): array
    {
        return ['type' => Column::TYPE_LONGTEXT];
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeEnum(Definition $column): array
    {
        return $this->checkedEnum($column);
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeBlob(Definition $column): array
    {
        return ['type' => Column::TYPE_BLOB];
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeTinyBlob(Definition $column): array
    {
        return ['type' => Column::TYPE_TINYBLOB];
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeMediumBlob(Definition $column): array
    {
        return ['type' => Column::TYPE_MEDIUMBLOB];
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeLongBlob(Definition $column): array
    {
        return ['type' => Column::TYPE_LONGBLOB];
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeDate(Definition $column): array
    {
        return ['type' => Column::TYPE_DATE];
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeDateTime(Definition $column): array
    {
        return $this->temporal(Column::TYPE_DATETIME, $column);
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeDateTimeTz(Definition $column): array
    {
        return $this->typeDateTime($column);
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeTime(Definition $column): array
    {
        return $this->temporal(Column::TYPE_TIME, $column);
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeTimeTz(Definition $column): array
    {
        return $this->typeTime($column);
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeTimestamp(Definition $column): array
    {
        return $this->temporal(Column::TYPE_TIMESTAMP, $column);
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeTimestampTz(Definition $column): array
    {
        return $this->typeTimestamp($column);
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeUuid(Definition $column): array
    {
        return ['type' => Column::TYPE_CHAR, 'size' => 36];
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeIpAddress(Definition $column): array
    {
        return ['type' => Column::TYPE_VARCHAR, 'size' => 45];
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeMacAddress(Definition $column): array
    {
        return ['type' => Column::TYPE_VARCHAR, 'size' => 17];
    }

    protected static function string(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}

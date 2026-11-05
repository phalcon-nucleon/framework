<?php

declare(strict_types=1);

namespace Neutrino\Model;

use Phalcon\Db\Column;
use Phalcon\Mvc\Model\MetaData;

/**
 * The columns of a model, described without database introspection: the meta-data and the column map given to
 * Phalcon by {@see MetaDataStrategy}.
 *
 * A column has a name in the table, and an attribute name in the model (the same unless mapped).
 */
final class Description
{
    /** @var list<string> */
    private array $attributes = [];

    /** @var list<string> */
    private array $primaryKey = [];

    /** @var list<string> */
    private array $nonPrimaryKey = [];

    /** @var list<string> */
    private array $notNull = [];

    /** @var array<string, int> */
    private array $dataTypes = [];

    /** @var array<string, true> */
    private array $numeric = [];

    /** @var array<string, int> */
    private array $bindTypes = [];

    private string|false $identity = false;

    /** @var array<string, true> */
    private array $autoInsert = [];

    /** @var array<string, true> */
    private array $autoUpdate = [];

    /** @var array<string, mixed> */
    private array $defaults = [];

    /** @var array<string, true> */
    private array $emptyStrings = [];

    /** @var array<string, string> column => attribute */
    private array $columnMap = [];

    /**
     * @param array{map?: string, identity?: bool, autoIncrement?: bool} $options
     */
    public function primary(string $name, ?int $type, array $options = []): self
    {
        $this->add($name, $type, $options['map'] ?? $name);

        $this->primaryKey[] = $name;
        $this->notNull[] = $name;

        if ($options['identity'] ?? true) {
            $this->identity = $name;
        }
        if ($options['autoIncrement'] ?? true) {
            $this->autoInsert[$name] = true;
        }

        return $this;
    }

    /**
     * @param array{map?: string, nullable?: bool, default?: mixed, autoInsert?: bool, autoUpdate?: bool} $options
     */
    public function column(string $name, ?int $type, array $options = []): self
    {
        $this->add($name, $type, $options['map'] ?? $name);

        $this->nonPrimaryKey[] = $name;

        if ($options['nullable'] ?? false) {
            $this->emptyStrings[$name] = true;
        } else {
            $this->notNull[] = $name;
        }
        if (array_key_exists('default', $options) && $options['default'] !== null) {
            $this->defaults[$name] = $options['default'];
        }
        if ($options['autoInsert'] ?? false) {
            $this->autoInsert[$name] = true;
        }
        if ($options['autoUpdate'] ?? false) {
            $this->autoUpdate[$name] = true;
        }

        return $this;
    }

    public function has(string $column): bool
    {
        return isset($this->columnMap[$column]);
    }

    /**
     * Phalcon meta-data (`MetaData::MODELS_*` indexes).
     *
     * @return array<int, mixed>
     */
    public function metaData(): array
    {
        return [
            MetaData::MODELS_ATTRIBUTES               => $this->attributes,
            MetaData::MODELS_PRIMARY_KEY              => $this->primaryKey,
            MetaData::MODELS_NON_PRIMARY_KEY          => $this->nonPrimaryKey,
            MetaData::MODELS_NOT_NULL                 => $this->notNull,
            MetaData::MODELS_DATA_TYPES               => $this->dataTypes,
            MetaData::MODELS_DATA_TYPES_NUMERIC       => $this->numeric,
            MetaData::MODELS_DATE_AT                  => [],
            MetaData::MODELS_DATE_IN                  => [],
            MetaData::MODELS_IDENTITY_COLUMN          => $this->identity,
            MetaData::MODELS_DATA_TYPES_BIND          => $this->bindTypes,
            MetaData::MODELS_AUTOMATIC_DEFAULT_INSERT => $this->autoInsert,
            MetaData::MODELS_AUTOMATIC_DEFAULT_UPDATE => $this->autoUpdate,
            MetaData::MODELS_DEFAULT_VALUES           => $this->defaults,
            MetaData::MODELS_EMPTY_STRING_VALUES      => $this->emptyStrings,
        ];
    }

    /**
     * Phalcon column maps: `[MODELS_COLUMN_MAP => column => attribute, MODELS_REVERSE_COLUMN_MAP => attribute => column]`.
     *
     * @return array<int, array<string, string>|null>
     */
    public function columnMaps(): array
    {
        return [
            MetaData::MODELS_COLUMN_MAP         => $this->columnMap,
            MetaData::MODELS_REVERSE_COLUMN_MAP => array_flip($this->columnMap),
        ];
    }

    /**
     * Bind type of a column type (and whether it is numeric).
     *
     * @return array{int, bool}
     */
    public static function bindType(?int $type): array
    {
        return match ($type) {
            null => [Column::BIND_PARAM_NULL, false],
            Column::TYPE_BIGINTEGER, Column::TYPE_INTEGER, Column::TYPE_MEDIUMINTEGER, Column::TYPE_SMALLINTEGER,
            Column::TYPE_TINYINTEGER, Column::TYPE_TIMESTAMP => [Column::BIND_PARAM_INT, true],
            Column::TYPE_DECIMAL, Column::TYPE_FLOAT, Column::TYPE_DOUBLE => [Column::BIND_PARAM_DECIMAL, true],
            Column::TYPE_JSON, Column::TYPE_TEXT, Column::TYPE_TINYTEXT, Column::TYPE_MEDIUMTEXT, Column::TYPE_LONGTEXT,
            Column::TYPE_CHAR, Column::TYPE_VARCHAR, Column::TYPE_DATE, Column::TYPE_DATETIME, Column::TYPE_TIME,
            Column::TYPE_ENUM, Column::TYPE_UUID => [Column::BIND_PARAM_STR, false],
            Column::TYPE_BLOB, Column::TYPE_JSONB, Column::TYPE_MEDIUMBLOB, Column::TYPE_TINYBLOB, Column::TYPE_LONGBLOB,
            Column::TYPE_BINARY, Column::TYPE_VARBINARY => [Column::BIND_PARAM_BLOB, false],
            Column::TYPE_BOOLEAN => [Column::BIND_PARAM_BOOL, false],
            default => [Column::BIND_SKIP, false],
        };
    }

    private function add(string $name, ?int $type, string $attribute): void
    {
        $this->columnMap[$name] = $attribute;
        $this->attributes[] = $name;
        $this->dataTypes[$name] = $type ?? Column::TYPE_VARCHAR;

        [$bind, $numeric] = self::bindType($type);
        $this->bindTypes[$name] = $bind;

        if ($numeric) {
            $this->numeric[$name] = true;
        }
    }
}

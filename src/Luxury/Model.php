<?php

namespace Luxury;

use Phalcon\Db\Column;
use Phalcon\Mvc\Model\MetaData;

/**
 * Class Model
 *
 * @package Luxury
 */
abstract class Model extends \Phalcon\Mvc\Model
{
    /**
     * @var array
     */
    protected static $metaDatasClass = [];

    /**
     * @var array
     */
    protected static $columnsMapClass = [];

    /**
     * Initializes metaDatas & columnsMap if they are not.
     */
    public function initialize()
    {
        if (!isset(static::$metaDatasClass[static::class])) {
            static::initializeMetaData();
            $this->describe();
        }
    }

    /**
     * Return the metaData
     *
     * @return array
     */
    public function metaData()
    {
        return static::$metaDatasClass[static::class];
    }

    /**
     * Return the columnMap
     *
     * @return array
     */
    public function columnMap()
    {
        return static::$columnsMapClass[static::class];
    }

    /**
     * Describe the column of the model.
     *
     * Use the function "primary", "column" to describe them.
     *
     * @return void
     */
    protected function describe()
    {
    }

    /**
     * Initialize the metaData with all meta attributes.
     */
    private static function initializeMetaData()
    {
        static::$metaDatasClass[static::class] = [
            MetaData::MODELS_ATTRIBUTES               => [],
            MetaData::MODELS_PRIMARY_KEY              => [],
            MetaData::MODELS_NON_PRIMARY_KEY          => [],
            MetaData::MODELS_NOT_NULL                 => [],
            MetaData::MODELS_DATA_TYPES               => [],
            MetaData::MODELS_DATA_TYPES_NUMERIC       => [],
            MetaData::MODELS_DATE_AT                  => [],
            MetaData::MODELS_DATE_IN                  => [],
            MetaData::MODELS_IDENTITY_COLUMN          => false,
            MetaData::MODELS_DATA_TYPES_BIND          => [],
            MetaData::MODELS_AUTOMATIC_DEFAULT_INSERT => [],
            MetaData::MODELS_AUTOMATIC_DEFAULT_UPDATE => [],
            MetaData::MODELS_DEFAULT_VALUES           => [],
            MetaData::MODELS_EMPTY_STRING_VALUES      => []
        ];
    }

    /**
     * Define the primary column
     *
     * @param string $name
     * @param int    $type
     * @param bool   $identity
     * @param bool   $autoIncrement
     */
    protected static function primary($name, $type, $identity = true, $autoIncrement = true)
    {
        static::addColumn($name, $type);

        static::$metaDatasClass[static::class][MetaData::MODELS_PRIMARY_KEY][] = $name;
        static::$metaDatasClass[static::class][MetaData::MODELS_NOT_NULL][] = $name;

        if ($identity) {
            static::$metaDatasClass[static::class][MetaData::MODELS_IDENTITY_COLUMN] = $name;
        }
        if ($autoIncrement) {
            static::$metaDatasClass[static::class][MetaData::MODELS_AUTOMATIC_DEFAULT_INSERT][$name] = true;
        }
    }

    /**
     * Define a column
     *
     * @param string          $name
     * @param int             $type
     * @param bool            $nullable
     * @param string|int|null $default
     * @param bool            $autoUpdate
     */
    protected static function column($name, $type, $nullable = false, $default = null, $autoUpdate = false)
    {
        static::addColumn($name, $type);

        static::$metaDatasClass[static::class][MetaData::MODELS_NON_PRIMARY_KEY][] = $name;

        if ($nullable) {
            static::$metaDatasClass[static::class][MetaData::MODELS_EMPTY_STRING_VALUES][$name] = true;
        } else {
            static::$metaDatasClass[static::class][MetaData::MODELS_NOT_NULL][] = $name;
        }

        if (!is_null($default)) {
            static::$metaDatasClass[static::class][MetaData::MODELS_DEFAULT_VALUES][$name] = $default;
        }

        if ($autoUpdate) {
            static::$metaDatasClass[static::class][MetaData::MODELS_AUTOMATIC_DEFAULT_UPDATE][$name] = true;
        }
    }

    private static function addColumn($name, $type)
    {
        static::$columnsMapClass[static::class][$name] = $name;

        static::$metaDatasClass[static::class][MetaData::MODELS_ATTRIBUTES][] = $name;
        static::$metaDatasClass[static::class][MetaData::MODELS_DATA_TYPES][$name] = $type;

        static::describeColumnType($name, $type);
    }

    /**
     * Define a column type
     *
     * @param string $name
     * @param int    $type
     */
    private static function describeColumnType($name, $type)
    {
        if ($type === null) {
            static::$metaDatasClass[static::class][MetaData::MODELS_DATA_TYPES_BIND][$name] = Column::BIND_PARAM_NULL;
        } elseif (
            $type === Column::TYPE_BIGINTEGER ||
            $type === Column::TYPE_INTEGER ||
            $type === Column::TYPE_TIMESTAMP
        ) {
            static::$metaDatasClass[static::class][MetaData::MODELS_DATA_TYPES_BIND][$name] = Column::BIND_PARAM_INT;
            static::$metaDatasClass[static::class][MetaData::MODELS_DATA_TYPES_NUMERIC][$name] = true;
        } elseif (
            $type === Column::TYPE_DECIMAL ||
            $type === Column::TYPE_FLOAT ||
            $type === Column::TYPE_DOUBLE
        ) {
            static::$metaDatasClass[static::class][MetaData::MODELS_DATA_TYPES_BIND][$name] = Column::BIND_PARAM_DECIMAL;
            static::$metaDatasClass[static::class][MetaData::MODELS_DATA_TYPES_NUMERIC][$name] = true;
        } elseif (
            $type === Column::TYPE_JSON ||
            $type === Column::TYPE_TEXT ||
            $type === Column::TYPE_CHAR ||
            $type === Column::TYPE_VARCHAR ||
            $type === Column::TYPE_DATE ||
            $type === Column::TYPE_DATETIME
        ) {
            static::$metaDatasClass[static::class][MetaData::MODELS_DATA_TYPES_BIND][$name] = Column::BIND_PARAM_STR;
        } elseif (
            $type === Column::TYPE_BLOB ||
            $type === Column::TYPE_JSONB ||
            $type === Column::TYPE_MEDIUMBLOB ||
            $type === Column::TYPE_TINYBLOB ||
            $type === Column::TYPE_LONGBLOB
        ) {
            static::$metaDatasClass[static::class][MetaData::MODELS_DATA_TYPES_BIND][$name] = Column::BIND_PARAM_BLOB;
        } elseif ($type === Column::TYPE_BOOLEAN) {
            static::$metaDatasClass[static::class][MetaData::MODELS_DATA_TYPES_BIND][$name] = Column::BIND_PARAM_BOOL;
        } else {
            static::$metaDatasClass[static::class][MetaData::MODELS_DATA_TYPES_BIND][$name] = Column::BIND_SKIP;
        }
    }
}

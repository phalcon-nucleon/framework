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
    protected static $meta = [];

    /**
     * @return array
     */
    public function metaData()
    {
        if (!isset(static::$meta[static::class])) {
            static::describe();
        }

        return static::$meta[static::class];
    }

    protected static function describe()
    {
    }

    /**
     * Define the primary column
     *
     * @param string $name
     * @param int    $type
     */
    protected static function primary($name, $type)
    {
        static::$meta[static::class][MetaData::MODELS_IDENTITY_COLUMN]   = $name;
        static::$meta[static::class][MetaData::MODELS_PRIMARY_KEY][]     = $name;
        static::$meta[static::class][MetaData::MODELS_ATTRIBUTES][]      = $name;
        static::$meta[static::class][MetaData::MODELS_DATA_TYPES][$name] = $type;

        static::describeColumnType($name, $type);
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
        static::$meta[static::class][MetaData::MODELS_ATTRIBUTES][]      = $name;
        static::$meta[static::class][MetaData::MODELS_NON_PRIMARY_KEY][] = $name;
        static::$meta[static::class][MetaData::MODELS_DATA_TYPES][$name] = $type;

        if ($nullable) {
            static::$meta[static::class][MetaData::MODELS_EMPTY_STRING_VALUES][$name] = true;
        } else {
            static::$meta[static::class][MetaData::MODELS_NOT_NULL][] = $name;
        }

        if (!is_null($default)) {
            static::$meta[static::class][MetaData::MODELS_DEFAULT_VALUES][$name] = $default;
        }

        if ($autoUpdate) {
            static::$meta[static::class][MetaData::MODELS_AUTOMATIC_DEFAULT_UPDATE][$name] = true;
        }

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
        switch ($type) {
            case Column::TYPE_BIGINTEGER:
            case Column::TYPE_INTEGER:
            case Column::TYPE_TIMESTAMP:
                static::$meta[static::class][MetaData::MODELS_DATA_TYPES_BIND][$name] = Column::BIND_PARAM_INT;
                break;
            case Column::TYPE_DECIMAL:
            case Column::TYPE_FLOAT:
            case Column::TYPE_DOUBLE:
                static::$meta[static::class][MetaData::MODELS_DATA_TYPES_BIND][$name] = Column::BIND_PARAM_DECIMAL;
                break;
            case Column::TYPE_JSON:
            case Column::TYPE_TEXT:
            case Column::TYPE_CHAR:
            case Column::TYPE_VARCHAR:
            case Column::TYPE_DATE:
            case Column::TYPE_DATETIME:
                static::$meta[static::class][MetaData::MODELS_DATA_TYPES_BIND][$name] = Column::BIND_PARAM_STR;
                break;
            case Column::TYPE_BLOB:
            case Column::TYPE_JSONB:
            case Column::TYPE_MEDIUMBLOB:
            case Column::TYPE_TINYBLOB:
            case Column::TYPE_LONGBLOB:
                static::$meta[static::class][MetaData::MODELS_DATA_TYPES_BIND][$name] = Column::BIND_PARAM_BLOB;
                break;
            case Column::TYPE_BOOLEAN:
                static::$meta[static::class][MetaData::MODELS_DATA_TYPES_BIND][$name] = Column::BIND_PARAM_BOOL;
                break;
            default:
                static::$meta[static::class][MetaData::MODELS_DATA_TYPES_BIND][$name] = Column::BIND_SKIP;
        }
    }
}

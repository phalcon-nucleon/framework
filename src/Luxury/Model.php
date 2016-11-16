<?php

namespace Luxury;

use Phalcon\Db\Column;
use Phalcon\Mvc\Model\Behavior\SoftDelete;
use Phalcon\Mvc\Model\Behavior\Timestampable;
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
     * Define the primary column
     *
     * @param string $name
     * @param int    $type
     * @param array  $options
     */
    protected function primary($name, $type, array $options = [])
    {
        static::addColumn($name, $type, isset($options['map']) ? $options['map'] : $name);

        static::$metaDatasClass[static::class][MetaData::MODELS_PRIMARY_KEY][] = $name;
        static::$metaDatasClass[static::class][MetaData::MODELS_NOT_NULL][] = $name;

        if (
            (!isset($options['identity']) || $options['identity']) &&
            (!isset($options['multiple']) || !$options['multiple'])
        ) {
            static::$metaDatasClass[static::class][MetaData::MODELS_IDENTITY_COLUMN] = $name;
        }
        if (
            (!isset($options['autoIncrement']) || $options['autoIncrement']) &&
            (!isset($options['multiple']) || !$options['multiple'])
        ) {
            static::$metaDatasClass[static::class][MetaData::MODELS_AUTOMATIC_DEFAULT_INSERT][$name] = true;
        }
    }

    /**
     * Define a column
     *
     * @param string $name
     * @param int    $type
     * @param array  $options
     */
    protected function column($name, $type, array $options = [])
    {
        static::addColumn($name, $type, isset($options['map']) ? $options['map'] : $name);

        static::$metaDatasClass[static::class][MetaData::MODELS_NON_PRIMARY_KEY][] = $name;

        if (isset($options['nullable']) && $options['nullable']) {
            static::$metaDatasClass[static::class][MetaData::MODELS_EMPTY_STRING_VALUES][$name] = true;
        } else {
            static::$metaDatasClass[static::class][MetaData::MODELS_NOT_NULL][] = $name;
        }

        if (isset($options['default'])) {
            static::$metaDatasClass[static::class][MetaData::MODELS_DEFAULT_VALUES][$name] = $options['default'];
        }

        if (isset($options['autoInsert']) && $options['autoInsert']) {
            static::$metaDatasClass[static::class][MetaData::MODELS_AUTOMATIC_DEFAULT_INSERT][$name] = true;
        }

        if (isset($options['autoUpdate']) && $options['autoUpdate']) {
            static::$metaDatasClass[static::class][MetaData::MODELS_AUTOMATIC_DEFAULT_UPDATE][$name] = true;
        }
    }

    /**
     * Define a timestampable column.
     * Automatically add the Timestampable behavior.
     *
     * @param string $name
     * @param array  $options
     */
    protected function timestampable($name, array $options = [])
    {
        if ((isset($options['autoInsert']) && $options['autoInsert']) ||
            (isset($options['autoUpdate']) && $options['autoUpdate'])
        ) {
            throw new \RuntimeException('Model: A timestampable field can\'t have autoInsert or autoUpdate.');
        }
        if (isset($options['nullable']) && $options['nullable']) {
            throw new \RuntimeException('Model: A timestampable field can\'t be nullable.');
        }
        if (isset($options['default'])) {
            throw new \RuntimeException('Model: A timestampable field can\'t have a default value.');
        }

        self::column($name, isset($options['type']) ? $options['type'] : Column::TYPE_DATETIME, $options);

        $params = [];

        if (isset($options['insert']) && $options['insert']) {
            $params['beforeCreate'] = [
                'field'  => $name,
                'format' => isset($options['format']) ? $options['format'] : DATE_ATOM
            ];
        }

        if (isset($options['update']) && $options['update']) {
            $params['beforeUpdate'] = [
                'field'  => $name,
                'format' => isset($options['format']) ? $options['format'] : DATE_ATOM
            ];
        }

        if(empty($params)){
            throw new \RuntimeException('Model: A timestampable field needs to have at least insert or update.');
        }

        $this->addBehavior(new Timestampable($params));
    }

    /**
     * Add <created_at> & <updated_at> columns with  Timestampable behavior.
     */
    protected function timestamps()
    {
        $this->timestampable('created_at', ['insert' => true]);
        $this->timestampable('updated_at', ['update' => true]);
    }

    /**
     * Define a softDeleted column.
     * Automatically add the SoftDelete behavior.
     *
     * @param string $name
     * @param array  $options
     */
    protected function softDeletable($name, array $options = [])
    {
        if ((isset($options['autoInsert']) && $options['autoInsert']) ||
            (isset($options['autoUpdate']) && $options['autoUpdate'])
        ) {
            throw new \RuntimeException('Model: A timestampable field can\'t have autoInsert or autoUpdate.');
        }
        if (isset($options['default'])) {
            throw new \RuntimeException('Model: A timestampable field can\'t have a default value.');
        }

        self::column($name, isset($options['type']) ? $options['type'] : Column::TYPE_BOOLEAN, $options);

        $this->addBehavior(new SoftDelete([
            'field' => $name,
            'value' => isset($options['value']) ? $options['value'] : true
        ]));
    }

    /**
     * Add <deleted> column with SoftDelete behavior.
     */
    protected function softDelete()
    {
        $this->softDeletable('deleted');
    }

    /**
     *
     *
     * @param string $name
     * @param int    $type
     * @param string $map
     */
    private static function addColumn($name, $type, $map)
    {
        static::$columnsMapClass[static::class][$name] = $map;

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

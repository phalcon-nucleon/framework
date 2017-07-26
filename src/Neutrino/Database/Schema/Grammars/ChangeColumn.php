<?php

namespace Neutrino\Database\Schema\Grammars;

use RuntimeException;
use Neutrino\Support\Fluent;
use Phalcon\Db\Adapter as Connection;
use Neutrino\Database\Schema\Blueprint;

class ChangeColumn
{
    /**
     * Compile a change column command into a series of SQL statements.
     *
     * @param  \Neutrino\Database\Schema\Grammars\Grammar  $grammar
     * @param  \Neutrino\Database\Schema\Blueprint  $blueprint
     * @param  \Neutrino\Support\Fluent  $command
     * @param  \Phalcon\Db\Adapter $connection
     * @return array
     *
     * @throws \RuntimeException
     */
    public static function compile($grammar, Blueprint $blueprint, Fluent $command, Connection $connection)
    {
        $tableDiff = static::getChangedDiff(
            $grammar, $blueprint, $connection
        );

        if ($tableDiff !== false) {
            return (array) $schema->getDatabasePlatform()->getAlterTableSQL($tableDiff);
        }

        return [];
    }

    /**
     * Get the Doctrine table difference for the given changes.
     *
     * @param  \Neutrino\Database\Schema\Grammars\Grammar  $grammar
     * @param  \Neutrino\Database\Schema\Blueprint  $blueprint
     * @param  \Phalcon\Db\Adapter  $schema
     * @return \Doctrine\DBAL\Schema\TableDiff|bool
     */
    protected static function getChangedDiff($grammar, Blueprint $blueprint, Connection $schema)
    {
        $current = $schema->listTableDetails($grammar->getTablePrefix() . $blueprint->getTable());

        return (new Comparator)->diffTable(
            $current, static::getTableWithColumnChanges($blueprint, $current)
        );
    }

    /**
     * Get a copy of the given Doctrine table after making the column changes.
     *
     * @param  \Neutrino\Database\Schema\Blueprint  $blueprint
     * @param  \Doctrine\DBAL\Schema\Table  $table
     * @return \Doctrine\DBAL\Schema\Table
     */
    protected static function getTableWithColumnChanges(Blueprint $blueprint, Table $table)
    {
        $table = clone $table;

        foreach ($blueprint->getChangedColumns() as $fluent) {
            $column = static::getDoctrineColumn($table, $fluent);

            // Here we will spin through each fluent column definition and map it to the proper
            // Doctrine column definitions - which is necessary because Laravel and Doctrine
            // use some different terminology for various column attributes on the tables.
            foreach ($fluent->getAttributes() as $key => $value) {
                if (! is_null($option = static::mapFluentOptionToDoctrine($key))) {
                    if (method_exists($column, $method = 'set'.ucfirst($option))) {
                        $column->{$method}(static::mapFluentValueToDoctrine($option, $value));
                    }
                }
            }
        }

        return $table;
    }

    /**
     * Get the Doctrine column instance for a column change.
     *
     * @param  \Doctrine\DBAL\Schema\Table  $table
     * @param  \Neutrino\Support\Fluent  $fluent
     * @return \Doctrine\DBAL\Schema\Column
     */
    protected static function getDoctrineColumn(Table $table, Fluent $fluent)
    {
        return $table->changeColumn(
            $fluent['name'], static::getDoctrineColumnChangeOptions($fluent)
        )->getColumn($fluent['name']);
    }

    /**
     * Get the Doctrine column change options.
     *
     * @param  \Neutrino\Support\Fluent  $fluent
     * @return array
     */
    protected static function getDoctrineColumnChangeOptions(Fluent $fluent)
    {
        $options = ['type' => static::getDoctrineColumnType($fluent['type'])];

        if (in_array($fluent['type'], ['text', 'mediumText', 'longText'])) {
            $options['length'] = static::calculateDoctrineTextLength($fluent['type']);
        }

        return $options;
    }

    /**
     * Get the doctrine column type.
     *
     * @param  string  $type
     * @return \Doctrine\DBAL\Types\Type
     */
    protected static function getDoctrineColumnType($type)
    {
        $type = strtolower($type);

        switch ($type) {
            case 'biginteger':
                $type = 'bigint';
                break;
            case 'smallinteger':
                $type = 'smallint';
                break;
            case 'mediumtext':
            case 'longtext':
                $type = 'text';
                break;
            case 'binary':
                $type = 'blob';
                break;
        }

        return Type::getType($type);
    }

    /**
     * Calculate the proper column length to force the Doctrine text type.
     *
     * @param  string  $type
     * @return int
     */
    protected static function calculateDoctrineTextLength($type)
    {
        switch ($type) {
            case 'mediumText':
                return 65535 + 1;
            case 'longText':
                return 16777215 + 1;
            default:
                return 255 + 1;
        }
    }

    /**
     * Get the matching Doctrine option for a given Fluent attribute name.
     *
     * @param  string  $attribute
     * @return string|null
     */
    protected static function mapFluentOptionToDoctrine($attribute)
    {
        switch ($attribute) {
            case 'type':
            case 'name':
                return;
            case 'nullable':
                return 'notnull';
            case 'total':
                return 'precision';
            case 'places':
                return 'scale';
            default:
                return $attribute;
        }
    }

    /**
     * Get the matching Doctrine value for a given Fluent attribute.
     *
     * @param  string  $option
     * @param  mixed  $value
     * @return mixed
     */
    protected static function mapFluentValueToDoctrine($option, $value)
    {
        return $option == 'notnull' ? ! $value : $value;
    }
}

<?php

namespace Neutrino\Database\Schema;

use Neutrino\Database\Schema\Exception\CommandException;
use Neutrino\Database\Schema\Exception\UnknownCommandException;
use Phalcon\Db\AdapterInterface as Db;
use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;

/**
 * Class Blueprint
 *
 * @package Neutrino\Database\Schema
 */
class Blueprint
{
    /** @var string */
    protected $table;

    /** @var string FOR POSTGRESQL */
    protected $schema = null;

    /** @var Definition[] */
    protected $columns = [];

    /** @var Definition[] */
    protected $indexes = [];

    /** @var Definition[] */
    protected $references = [];

    /** @var Definition[] */
    protected $commands = [];

    /** @var array */
    protected $options = [];

    /** @var int */
    protected $action;

    /**
     * Blueprint constructor.
     *
     * @param $table
     */
    public function __construct($table)
    {
        $this->table = $table;
    }

    /**
     * Execute the blueprint against the database.
     *
     * @param \Phalcon\Db\AdapterInterface               $db
     * @param \Neutrino\Database\Schema\DialectInterface $grammar
     *
     * @return bool
     * @throws \Exception
     */
    public function build(Db $db, DialectInterface $grammar)
    {
        switch ($this->action) {
            case 'create':
                return $this->buildCreate($db, $grammar);
            case 'update':
                return $this->buildUpdate($db, $grammar);
            case 'dropIfExists':
                return $this->buildDrop($db, true);
            case 'drop':
                return $this->buildDrop($db, false);
            default:
                if (empty($this->action)) {
                    $message = "Blueprint must has action";
                } else {
                    $message = "Action '{$this->action}' not supported.";
                }
                throw new \RuntimeException($message);
        }
    }

    /**
     * @param \Phalcon\Db\AdapterInterface               $db
     * @param \Neutrino\Database\Schema\DialectInterface $grammar
     *
     * @return bool
     */
    protected function buildCreate(Db $db, DialectInterface $grammar)
    {
        return $db->createTable($this->table, $this->schema, $this->buildTableDefinition($grammar));
    }

    /**
     * Build the table definition for table creation
     *
     * @param \Neutrino\Database\Schema\DialectInterface $grammar
     *
     * @return array
     */
    protected function buildTableDefinition(DialectInterface $grammar)
    {
        // Manage primary key / multiple primary keys
        $primaries = [];

        // Extract defined primary index
        foreach ($this->indexes as $key => $index) {
            if ($index->get('type') == 'primary') {
                foreach ($index->get('columns') as $column) {
                    $this->columns[$column]->primary();
                }

                unset($this->indexes[$key]);
            }
        }

        // Extract defined primary column
        foreach ($this->columns as $column) {
            if ($column->get('primary')) {
                $primaries[] = $column->get('name');

                unset($column['primary']);
            }
        }

        // Re-Build primary index
        if (($cprimaries = count($primaries)) === 1) {
            $this->columns[array_shift($primaries)]->primary();
        } elseif ($cprimaries > 0) {
            $this->primary($primaries);
        }

        // Build table definition
        $definition = [];

        foreach ($this->columns as $column) {
            $definition['columns'][] = $this->fluentToColumn($column, $grammar);
        }

        foreach ($this->indexes as $index) {
            $definition['indexes'][] = $this->fluentToIndex($index, $grammar);
        }

        foreach ($this->references as $reference) {
            $definition['references'][] = $this->fluentToReference($reference);
        }

        foreach ($this->options as $name => $value) {
            $definition['options'][strtoupper($name)] = $value;
        }

        return $definition;
    }

    /**
     * @param \Phalcon\Db\AdapterInterface               $db
     * @param \Neutrino\Database\Schema\DialectInterface $grammar
     *
     * @return bool
     * @throws \Exception
     */
    protected function buildUpdate(Db $db, DialectInterface $grammar)
    {
        $this->buildCommands($db);

        $table = $this->table;
        $schema = $this->schema;

        foreach ($this->commands as $command) {
            switch ($command->get('name')) {
                case 'addColumn':
                    $res = $db->addColumn(
                        $table,
                        $schema,
                        $this->fluentToColumn($command->get('column'), $grammar)
                    );
                    break;
                case 'renameColumn':
                    $columns = $db->describeColumns($table, $schema);
                    $from = null;
                    foreach ($columns as $column) {
                        if ($column->getName() === $command->get('from')) {
                            $from = $column;
                            break;
                        }
                    }
                    $definition = [
                        'type'          => $from->getType(),
                        'notNull'       => $from->isNotNull(),
                        'primary'       => $from->isPrimary(),
                        'autoIncrement' => $from->isAutoIncrement(),
                        'numeric'       => $from->isNumeric(),
                    ];

                    if (($typeReference = $from->getTypeReference()) !== -1) {
                        $definition['typeReference'] = $typeReference;
                    }
                    if (($typeValues = $from->getTypeValues()) !== -1) {
                        $definition['typeValues'] = $typeValues;
                    }
                    if (!empty($size = $from->getSize())) {
                        $definition['size'] = $size;
                    }
                    if (!is_null($default = $from->getDefault())) {
                        $definition['default'] = $default;
                    }
                    if (!empty($scale = $from->getScale())) {
                        $definition['scale'] = $scale;
                    }

                    $to = new Column($command->get('to'), $definition);

                    $res = $db->modifyColumn($table, $schema, $to, $from);
                    break;
                case 'modifyColumn':
                    $res = $db->modifyColumn(
                        $table,
                        $schema,
                        $this->fluentToColumn($command->get('column'), $grammar),
                        $command->get('from')
                    );
                    break;
                case 'addIndex':
                    $res = $db->addIndex(
                        $table,
                        $schema,
                        $this->fluentToIndex($command->get('index'), $grammar)
                    );
                    break;
                case 'addForeign':
                    $res = $db->addForeignKey(
                        $table,
                        $schema,
                        $this->fluentToReference($command->get('reference'))
                    );
                    break;
                case 'dropColumn':
                    $res = $db->dropColumn(
                        $table,
                        $schema,
                        $command->get('column')
                    );
                    break;
                case 'dropForeign':
                    $res = true;
                    foreach ((array)$command->get('reference') as $reference) {
                        $res = $res && $db->dropForeignKey($table, $schema, $reference);
                        if (!$res) {
                            break;
                        }
                    }
                    break;
                case 'dropIndex':
                    $res = true;
                    foreach ((array)$command->get('index') as $index) {
                        $res &= $db->dropIndex($table, $schema, $index);
                        if (!$res) {
                            break;
                        }
                    }
                    break;
                case 'dropPrimary':
                    $res = $db->dropPrimaryKey($table, $schema);
                    break;
                case 'rename':
                    /* TODO */
                    $res = false;
                    break;
                default:
                    throw new UnknownCommandException($command);
            }

            if ($res === false) {
                throw new CommandException($command);
            }
        }

        return true;
    }

    /**
     * Build all columns, indexes, references, to commands
     *
     * @param \Phalcon\Db\AdapterInterface $db
     */
    protected function buildCommands(Db $db)
    {
        $columns = $db->describeColumns($this->table, $this->schema);
        foreach ($this->columns as $column) {
            $this->buildIndexAndForeignFromFluentColumn($column);

            foreach ($columns as $c) {
                if ($c->getName() === $column->get('name')) {
                    $this->addCommand('modifyColumn', ['column' => $column, 'from' => $c]);
                    continue 2;
                }
            }

            $this->addCommand('addColumn', ['column' => $column]);
        }

        foreach ($this->indexes as $index) {
            $this->addCommand('addIndex', ['index' => $index]);
        }

        foreach ($this->references as $reference) {
            $this->addCommand('addForeign', ['reference' => $reference]);
        }
    }

    /**
     * @param \Phalcon\Db\AdapterInterface $connection
     * @param bool                         $ifExist
     *
     * @return bool
     */
    protected function buildDrop(Db $connection, $ifExist = false)
    {
        return $connection->dropTable($this->table, $this->schema, $ifExist);
    }

    /**
     * @param \Neutrino\Database\Schema\Definition $column
     */
    protected function buildIndexAndForeignFromFluentColumn(Definition $column)
    {
        $attributes = $column->getAttributes();

        if (isset($attributes['unique']) && $attributes['unique']) {
            $this->unique($column->get('name'), is_bool($attributes['unique']) ? null : $attributes['unique']);

            unset($column['unique']);
        } elseif (isset($attributes['index']) && $attributes['index']) {
            $this->index($column->get('name'), is_bool($attributes['index']) ? null : $attributes['index']);

            unset($column['index']);
        } elseif ($column->get('foreign')) {
            $this->foreign($column->get('name'))
                ->on($column->get('on'))
                ->references($column->get('references'))
                ->onUpdate($column->get('onUpdate'))
                ->onDelete($column->get('onDelete'));

            unset($column['foreign']);
            unset($column['on']);
            unset($column['references']);
            unset($column['onUpdate']);
            unset($column['onDelete']);
        }
    }

    /**
     * Transform a Fluent(Column) to a \Phalcon\Db\Column
     *
     * @param \Neutrino\Database\Schema\Definition       $column
     * @param \Neutrino\Database\Schema\DialectInterface $grammar
     *
     * @return \Phalcon\Db\Column
     */
    protected function fluentToColumn(Definition $column, DialectInterface $grammar)
    {
        $attributes = $column->getAttributes();

        $this->buildIndexAndForeignFromFluentColumn($column);

        $types = $grammar->getType($column);

        if (isset($attributes['type'])) {
            unset($attributes['type']);
        }
        if (isset($attributes['typeReference'])) {
            unset($attributes['typeReference']);
        }
        if (isset($attributes['typeValues'])) {
            unset($attributes['typeValues']);
        }

        if (isset($attributes['nullable'])) {
            $attributes['notNull'] = !$attributes['nullable'];
        } else {
            $attributes['notNull'] = true;
        }

        return new Column($column->get('name'), array_merge($types, $attributes));
    }

    /**
     * Transform a Fluent(Index) to a \Phalcon\Db\Index
     *
     * @param \Neutrino\Database\Schema\Definition                   $index
     * @param \Neutrino\Database\Schema\DialectInterface $grammar
     *
     * @return \Phalcon\Db\Index
     */
    protected function fluentToIndex(Definition $index, DialectInterface $grammar)
    {
        $name = $index->get('name') ?: $this->createIndexName($index->get('type'), $index->get('columns'));

        return new Index($name, $index->get('columns'), $grammar->getIndexType($index));
    }

    /**
     * Transform a Fluent(Reference) to a \Phalcon\Db\Reference
     *
     * @param \Neutrino\Database\Schema\Definition $index
     *
     * @return \Phalcon\Db\Reference
     */
    protected function fluentToReference(Definition $index)
    {
        $columns = (array)$index->get('columns');
        $references = (array)$index->get('references');

        $name = $index->get('name') ?: $this->createReferenceName($columns, $index->get('on'), $references);

        $definition = [
          'columns'           => $columns,
          'referencedTable'   => $index->get('on'),
          'referencedColumns' => $references,
        ];

        if ($index->get('onDelete')) {
            $definition['onDelete'] = $index->get('onDelete');
        }
        if ($index->get('onUpdate')) {
            $definition['onUpdate'] = $index->get('onUpdate');
        }

        return new Reference($name, $definition);
    }

    /**
     * Indicate that the table needs to be temporary.
     *
     * @return $this
     */
    public function temporary()
    {
        return $this->option(__FUNCTION__, true);
    }

    /**
     * Indicate that the table needs to be created.
     *
     * @return $this
     */
    public function create()
    {
        $this->action = __FUNCTION__;

        return $this;
    }

    /**
     * Indicate that the table needs to be updated.
     *
     * @return $this
     */
    public function update()
    {
        $this->action = __FUNCTION__;

        return $this;
    }

    /**
     * Indicate that the table should be dropped.
     *
     * @return $this
     */
    public function drop()
    {
        $this->action = __FUNCTION__;

        return $this;
    }

    /**
     * Indicate that the table should be dropped if it exists.
     *
     * @return $this
     */
    public function dropIfExists()
    {
        $this->action = __FUNCTION__;

        return $this;
    }

    /**
     * Indicate that the given columns should be dropped.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function dropColumn($column)
    {
        return $this->addCommand(__FUNCTION__, ['column' => $column]);
    }

    /**
     * Indicate that the given columns should be dropped.
     *
     * @param  string[] $columns
     */
    public function dropColumns($columns)
    {
        foreach ($columns as $column) {
            $this->dropColumn($column);
        }
    }

    /**
     * Indicate that the given columns should be renamed.
     *
     * @param  string $from
     * @param  string $to
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function renameColumn($from, $to)
    {
        return $this->addCommand(__FUNCTION__, ['from' => $from, 'to' => $to]);
    }

    /**
     * Indicate that the given primary key should be dropped.
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function dropPrimary()
    {
        return $this->addCommand(__FUNCTION__);
    }

    /**
     * Indicate that the given unique key should be dropped.
     *
     * @param  string|array $index
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function dropUnique($index)
    {
        return $this->dropIndex($index);
    }

    /**
     * Indicate that the given index should be dropped.
     *
     * @param  string|array $index
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function dropIndex($index)
    {
        return $this->addCommand(__FUNCTION__, ['index' => $index]);
    }

    /**
     * Indicate that the given foreign key should be dropped.
     *
     * @param  string|array $reference
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function dropForeign($reference)
    {
        return $this->addCommand(__FUNCTION__, ['reference' => $reference]);
    }

    /**
     * Indicate that the timestamp columns should be dropped.
     *
     * @return void
     */
    public function dropTimestamps()
    {
        $this->dropColumns(['created_at', 'updated_at']);
    }

    /**
     * Indicate that the timestamp columns should be dropped.
     *
     * @return void
     */
    public function dropTimestampsTz()
    {
        $this->dropTimestamps();
    }

    /**
     * Indicate that the soft delete column should be dropped.
     *
     * @return void
     */
    public function dropSoftDeletes()
    {
        $this->dropColumn('deleted_at');
    }

    /**
     * Indicate that the soft delete column should be dropped.
     *
     * @return void
     */
    public function dropSoftDeletesTz()
    {
        $this->dropSoftDeletes();
    }

    /**
     * Indicate that the remember token column should be dropped.
     *
     * @return void
     */
    public function dropRememberToken()
    {
        $this->dropColumn('remember_token');
    }

    /**
     * Define schema. FOR POSTGRESQL.
     *
     * @param  string $schema
     *
     * @return $this
     */
    public function schema($schema)
    {
        $this->schema = $schema;

        return $this;
    }

    /**
     * Rename the table to a given name.
     *
     * @param  string $to
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function rename($to)
    {
        return $this->addCommand(__FUNCTION__, ['to' => $to]);
    }

    /**
     * Specify the primary key(s) for the table.
     *
     * @param  string|array $columns
     * @param  string|null  $name
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function primary($columns, $name = null)
    {
        return $this->addIndex(__FUNCTION__, $columns, $name);
    }

    /**
     * Specify a unique index for the table.
     *
     * @param  string|array $columns
     * @param  string|null  $name
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function unique($columns, $name = null)
    {
        return $this->addIndex(__FUNCTION__, $columns, $name);
    }

    /**
     * Specify an index for the table.
     *
     * @param  string|array $columns
     * @param  string|null  $name
     * @param  string       $type Type of index
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function index($columns, $name = null, $type = __FUNCTION__)
    {
        return $this->addIndex($type, $columns, $name);
    }

    /**
     * Specify a foreign key for the table.
     *
     * @param  string|array $columns
     * @param  string|null  $name
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function foreign($columns, $name = null)
    {
        return $this->addForeign($columns, $name);
    }

    /**
     * Create a new auto-incrementing integer (4-byte) column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function increments($column)
    {
        return $this->unsignedInteger($column, true);
    }

    /**
     * Create a new auto-incrementing tiny integer (1-byte) column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function tinyIncrements($column)
    {
        return $this->unsignedTinyInteger($column, true);
    }

    /**
     * Create a new auto-incrementing small integer (2-byte) column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function smallIncrements($column)
    {
        return $this->unsignedSmallInteger($column, true);
    }

    /**
     * Create a new auto-incrementing medium integer (3-byte) column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function mediumIncrements($column)
    {
        return $this->unsignedMediumInteger($column, true);
    }

    /**
     * Create a new auto-incrementing big integer (8-byte) column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function bigIncrements($column)
    {
        return $this->unsignedBigInteger($column, true);
    }

    /**
     * Create a new char column on the table.
     *
     * @param  string $column
     * @param  int    $length
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function char($column, $length = null)
    {
        return $this->addColumn(__FUNCTION__, $column, ['size' => $length ?: Builder::$defaultStringLength]);
    }

    /**
     * Create a new string column on the table.
     *
     * @param  string   $column
     * @param  int|null $length
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function string($column, $length = null)
    {
        return $this->addColumn(__FUNCTION__, $column, ['size' => $length ?: Builder::$defaultStringLength]);
    }

    /**
     * Create a new text column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function text($column)
    {
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * Create a new medium text column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function mediumText($column)
    {
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * Create a new long text column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function longText($column)
    {
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * @param string $type
     * @param string $column
     * @param bool   $autoIncrement
     * @param bool   $unsigned
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    protected function addInteger($type, $column, $autoIncrement = false, $unsigned = false)
    {
        $column = $this->addColumn($type, $column, ['autoIncrement' => $autoIncrement, 'unsigned' => $unsigned]);

        if ($autoIncrement) {
            $column->primary();
        }

        return $column;
    }

    /**
     * Create a new integer (4-byte) column on the table.
     *
     * @param  string $column
     * @param  bool   $autoIncrement
     * @param  bool   $unsigned
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function integer($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addInteger(__FUNCTION__, $column, $autoIncrement, $unsigned);
    }

    /**
     * Create a new tiny integer (1-byte) column on the table.
     *
     * @param  string $column
     * @param  bool   $autoIncrement
     * @param  bool   $unsigned
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function tinyInteger($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addInteger(__FUNCTION__, $column, $autoIncrement, $unsigned);
    }

    /**
     * Create a new small integer (2-byte) column on the table.
     *
     * @param  string $column
     * @param  bool   $autoIncrement
     * @param  bool   $unsigned
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function smallInteger($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addInteger(__FUNCTION__, $column, $autoIncrement, $unsigned);
    }

    /**
     * Create a new medium integer (3-byte) column on the table.
     *
     * @param  string $column
     * @param  bool   $autoIncrement
     * @param  bool   $unsigned
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function mediumInteger($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addInteger(__FUNCTION__, $column, $autoIncrement, $unsigned);
    }

    /**
     * Create a new big integer (8-byte) column on the table.
     *
     * @param  string $column
     * @param  bool   $autoIncrement
     * @param  bool   $unsigned
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function bigInteger($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addInteger(__FUNCTION__, $column, $autoIncrement, $unsigned);
    }

    /**
     * Create a new unsigned integer (4-byte) column on the table.
     *
     * @param  string $column
     * @param  bool   $autoIncrement
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function unsignedInteger($column, $autoIncrement = false)
    {
        return $this->integer($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned tiny integer (1-byte) column on the table.
     *
     * @param  string $column
     * @param  bool   $autoIncrement
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function unsignedTinyInteger($column, $autoIncrement = false)
    {
        return $this->tinyInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned small integer (2-byte) column on the table.
     *
     * @param  string $column
     * @param  bool   $autoIncrement
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function unsignedSmallInteger($column, $autoIncrement = false)
    {
        return $this->smallInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned medium integer (3-byte) column on the table.
     *
     * @param  string $column
     * @param  bool   $autoIncrement
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function unsignedMediumInteger($column, $autoIncrement = false)
    {
        return $this->mediumInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned big integer (8-byte) column on the table.
     *
     * @param  string $column
     * @param  bool   $autoIncrement
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function unsignedBigInteger($column, $autoIncrement = false)
    {
        return $this->bigInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new float column on the table.
     *
     * @param  string $column
     * @param  int    $scale
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function float($column, $scale = null)
    {
        return $this->addColumn(__FUNCTION__, $column, is_int($scale) ? ['scale' => $scale] : []);
    }

    /**
     * Create a new double column on the table.
     *
     * @param  string $column
     * @param  int    $scale
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function double($column, $scale = null)
    {
        return $this->addColumn(__FUNCTION__, $column, is_int($scale) ? ['scale' => $scale] : []);
    }

    /**
     * Create a new decimal column on the table.
     *
     * @param  string $column
     * @param  int    $scale
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function decimal($column, $scale = null)
    {
        return $this->addColumn(__FUNCTION__, $column, is_int($scale) ? ['scale' => $scale] : []);
    }

    /**
     * Create a new boolean column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function boolean($column)
    {
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * Create a new enum column on the table.
     *
     * @param  string $column
     * @param  array  $allowed
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function enum($column, array $allowed)
    {
        return $this->addColumn(__FUNCTION__, $column, ['values' => $allowed]);
    }

    /**
     * Create a new json column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function json($column)
    {
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * Create a new jsonb column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function jsonb($column)
    {
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * Create a new date column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function date($column)
    {
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * Create a new date-time column on the table.
     *
     * @param  string $column
     * @param  int    $precision
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function dateTime($column, $precision = 0)
    {
        return $this->addColumn(__FUNCTION__, $column, ['precision' => $precision]);
    }

    /**
     * /**
     * Create a new date-time column (with time zone) on the table.
     *
     * @param  string $column
     * @param  int    $precision
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function dateTimeTz($column, $precision = 0)
    {
        return $this->addColumn(__FUNCTION__, $column, ['precision' => $precision]);
    }

    /**
     * Create a new time column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function time($column)
    {
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * Create a new time column (with time zone) on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function timeTz($column)
    {
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * Create a new timestamp column on the table.
     *
     * @param  string $column
     * @param  int    $precision
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function timestamp($column, $precision = 0)
    {
        return $this->addColumn(__FUNCTION__, $column, ['precision' => $precision]);
    }

    /**
     * Create a new timestamp (with time zone) column on the table.
     *
     * @param  string $column
     * @param  int    $precision
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function timestampTz($column, $precision = 0)
    {
        return $this->addColumn(__FUNCTION__, $column, ['precision' => $precision]);
    }

    /**
     * Add creation and update timestamps to the table.
     *
     * @param  int $precision
     *
     * @return void
     */
    public function timestamps($precision = 0)
    {
        $this->timestamp('created_at', $precision)->default('CURRENT_TIMESTAMP');

        $this->timestamp('updated_at', $precision)->default('CURRENT_TIMESTAMP')->onUpdate('CURRENT_TIMESTAMP');
    }

    /**
     * Add nullable creation and update timestamps to the table.
     *
     * @param  int $precision
     *
     * @return void
     */
    public function nullableTimestamps($precision = 0)
    {
        $this->timestamps($precision);

        $this->columns['created_at']->nullable();
        $this->columns['updated_at']->nullable();
    }

    /**
     * Add creation and update timestampTz columns to the table.
     *
     * @param  int $precision
     *
     * @return void
     */
    public function timestampsTz($precision = 0)
    {
        $this->timestampTz('created_at', $precision)->default('CURRENT_TIMESTAMP');

        $this->timestampTz('updated_at', $precision)->default('CURRENT_TIMESTAMP')->onUpdate('CURRENT_TIMESTAMP');
    }

    /**
     * Add nullable creation and update timestampTz to the table.
     *
     * @param  int $precision
     *
     * @return void
     */
    public function nullableTimestampsTz($precision = 0)
    {
        $this->timestampsTz($precision);

        $this->columns['created_at']->nullable();
        $this->columns['updated_at']->nullable();
    }

    /**
     * Add a "deleted at" timestamp for the table.
     *
     * @param  string $column
     * @param  int    $precision
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function softDeletes($column = 'deleted_at', $precision = 0)
    {
        return $this->timestamp($column, $precision)->nullable();
    }

    /**
     * Add a "deleted at" timestampTz for the table.
     *
     * @param  string $column
     * @param  int    $precision
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function softDeletesTz($column = 'deleted_at', $precision = 0)
    {
        return $this->timestampTz($column, $precision)->nullable();
    }

    /**
     * Create a new binary column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function binary($column)
    {
        return $this->blob($column);
    }

    /**
     * Create a new binary column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function blob($column)
    {
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * Create a new tiny binary column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function tinyBlob($column)
    {
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * Create a new medium binary column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function mediumBlob($column)
    {
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * Create a new long binary column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function longBlob($column)
    {
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * Create a new uuid column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function uuid($column)
    {
        /* @todo Uuid behavior */
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * Create a new IP address column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function ipAddress($column)
    {
        /* @todo IpAddress behavior */
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * Create a new MAC address column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function macAddress($column)
    {
        /* @todo MacAddress behavior */
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * Add the proper columns for a polymorphic table.
     *
     * @param  string      $name
     * @param  string|null $indexName
     *
     * @return void
     */
    public function morphs($name, $indexName = null)
    {
        $this->unsignedInteger("{$name}_id");

        $this->string("{$name}_type");

        $this->index(["{$name}_id", "{$name}_type"], $indexName);
    }

    /**
     * Add nullable columns for a polymorphic table.
     *
     * @param  string      $name
     * @param  string|null $indexName
     *
     * @return void
     */
    public function nullableMorphs($name, $indexName = null)
    {
        $this->unsignedInteger("{$name}_id")->nullable();

        $this->string("{$name}_type")->nullable();

        $this->index(["{$name}_id", "{$name}_type"], $indexName);
    }

    /**
     * Adds the `remember_token` column to the table.
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    public function rememberToken()
    {
        return $this->string('remember_token', 100)->nullable();
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $name
     * @param string $value
     *
     * @return $this
     */
    public function option($name, $value)
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * Get the table the blueprint describes.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Get the columns on the blueprint.
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Get the commands on the blueprint.
     *
     * @return array
     */
    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * Get the indexes on the blueprint.
     *
     * @return array
     */
    public function getIndexes()
    {
        return $this->indexes;
    }

    /**
     * Get the references on the blueprint.
     *
     * @return array
     */
    public function getReferences()
    {
        return $this->references;
    }

    /**
     * Add a new column to the blueprint.
     *
     * @param  int    $type
     * @param  string $name
     * @param  array  $parameters
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    protected function addColumn($type, $name, array $parameters = [])
    {
        return $this->columns[$name] = new Definition(array_merge([
            'name' => $name,
            "type" => $type,
        ], $parameters));
    }

    /**
     * Add a new command to the blueprint.
     *
     * @param  string $name
     * @param  array  $parameters
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    protected function addCommand($name, array $parameters = [])
    {
        $this->commands[] = $command = $this->createCommand($name, $parameters);

        return $command;
    }

    /**
     * Create a new Fluent command.
     *
     * @param  string $name
     * @param  array  $parameters
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    protected function createCommand($name, array $parameters = [])
    {
        return new Definition(array_merge(['name' => $name], $parameters));
    }

    /**
     * Add a new command to the blueprint.
     *
     * @param  string          $type
     * @param  string|string[] $columns
     * @param  string|null     $name
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    protected function addIndex($type, $columns, $name = null)
    {
        $columns = (array)$columns;

        // If no name was specified for this index, we will create one using a basic
        // convention of the table name, followed by the columns, followed by an
        // index type, such as primary or index, which makes the index unique.
        $name = $name ?: $this->createIndexName($type, $columns);

        return $this->indexes[] = new Definition(['name' => $name, 'columns' => $columns, 'type' => $type]);
    }

    /**
     * Add a new command to the blueprint.
     *
     * @param  string|string[] $columns
     * @param  string|null     $name
     *
     * @return \Neutrino\Database\Schema\Definition
     */
    protected function addForeign($columns, $name = null)
    {
        $columns = (array)$columns;

        // If no name was specified for this index, we will create one using a basic
        // convention of the table name, followed by the columns, followed by an
        // index type, such as primary or index, which makes the index unique.

        return $this->references[] = new Definition(['name' => $name, 'columns' => $columns, 'type' => 'foreign']);
    }

    /**
     * Create a default index name for the table.
     *
     * @param  string $type
     * @param  array  $columns
     *
     * @return string
     */
    protected function createIndexName($type, array $columns)
    {
        $index = strtolower(implode('_', array_filter([$this->table, implode('_', $columns), $type])));

        return trim(str_replace(['-', '.'], '_', $index), '_');
    }

    /**
     * Create a default index name for the table.
     *
     * @param  array  $columns
     * @param  string $on
     * @param  array  $references
     *
     * @return string
     */
    protected function createReferenceName(array $columns, $on, array $references)
    {
        $strColumns = implode('_', $columns);
        $strReferences = implode('_', $references);

        $rawReferenceName = [$this->table, $strColumns, 'foreign', $on, $strReferences];

        $rawReferenceName = array_map(function ($value) {
            return trim(str_replace(['-', '.'], '_', $value), '_');
        }, $rawReferenceName);

        $rawReferenceName = array_filter($rawReferenceName);

        $index = strtolower(trim(implode('_', $rawReferenceName), '_'));

        return $index;
    }
}

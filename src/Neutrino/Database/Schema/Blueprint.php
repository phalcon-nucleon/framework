<?php

namespace Neutrino\Database\Schema;

use Closure;
use Neutrino\Support\Fluent;
use Neutrino\Support\Str;
use Neutrino\Support\Traits\Macroable;
use Phalcon\Config;
use Phalcon\Db\AdapterInterface as Db;

class Blueprint
{
    use Macroable;

    /**
     * The table the blueprint describes.
     *
     * @var string
     */
    protected $table;

    /**
     * The columns that should be added to the table.
     *
     * @var \Neutrino\Database\Schema\Column[]
     */
    protected $columns = [];

    /**
     * The columns that should be added to the table.
     *
     * @var \Neutrino\Database\Schema\Index[]
     */
    protected $indexes = [];

    /**
     * The columns that should be added to the table.
     *
     * @var \Neutrino\Database\Schema\Reference[]
     */
    protected $references = [];

    /**
     * The commands that should be run for the table.
     *
     * @var array
     */
    protected $commands = [];

    /**
     * The storage engine that should be used for the table.
     *
     * @var string
     */
    public $engine;

    /**
     * The default character set that should be used for the table.
     */
    public $charset;

    /**
     * The collation that should be used for the table.
     */
    public $collation;

    /**
     * Whether to make the table temporary.
     *
     * @var bool
     */
    public $temporary = false;

    /**
     * Create a new schema blueprint.
     *
     * @param  string        $table
     * @param  \Closure|null $callback
     */
    public function __construct($table, Closure $callback = null)
    {
        $this->table = $table;

        if (!is_null($callback)) {
            $callback($this);
        }
    }

    /**
     * Execute the blueprint against the database.
     *
     * @param \Phalcon\Db\AdapterInterface $connection
     * @param \Phalcon\Config              $dbConfig
     *
     * @return void
     */
    public function build(Db $connection, Config $dbConfig)
    {
        $this->addImpliedCommands();

        foreach ($this->commands as $command) {
            switch ($command->name) {
                case 'create':
                    $connection->createTable($this->getTable(), $dbConfig->get('dbname'), [
                        'columns' => $this->columns,
                        'indexes' => $this->indexes,
                        'references' => $this->references,
                    ]);
                    break 2;
                case 'rename':
                    /* TODO */
                    break;
                case 'drop':
                    $connection->dropTable($this->getTable(), $dbConfig->get('dbname'), false);
                    break;
                case 'dropIfExists':
                    if ($connection->tableExists($this->getTable())){
                        $connection->dropTable($this->getTable(), $dbConfig->get('dbname'), true);
                    }
                    break;
                case 'dropColumn':
                    $connection->dropColumn($this->getTable(), $dbConfig->get('dbname'), $command->column);
                    break;
                case 'addColumn':
                    $connection->addColumn($this->getTable(), $dbConfig->get('dbname'), $command->column);
                    break;
                case 'renameColumn':
                case 'modifyColumn':
                    $connection->modifyColumn($this->getTable(), $dbConfig->get('dbname'), $command->column);
                    break;
                case 'addIndex':
                case 'addUnique':
                    $connection->addIndex($this->getTable(), $dbConfig->get('dbname'), $command->index);
                    break;
                case 'dropIndex':
                case 'dropUnique':
                    $connection->dropIndex($this->getTable(), $dbConfig->get('dbname'), $command->index);
                    break;
                case 'addPrimary':
                    $connection->addPrimaryKey($this->getTable(), $dbConfig->get('dbname'), $command->index);
                    break;
                case 'dropPrimary':
                    $connection->dropPrimaryKey($this->getTable(), $dbConfig->get('dbname'));
                    break;
                case 'addForeign':
                    $connection->addForeignKey($this->getTable(), $dbConfig->get('dbname'), $command->reference);
                    break;
                case 'dropForeign':
                    $connection->dropForeignKey($this->getTable(), $dbConfig->get('dbname'), $command->reference);
                    break;
            }
        }
    }

    /**
     * Get the raw SQL statements for the blueprint.
     *
     * @param  \Neutrino\Database\Schema\Grammars\Grammar $grammar
     *
     * @return array
     */
    public function toSql(Grammars\Grammar $grammar)
    {
        $this->addImpliedCommands();

        $statements = [];

        // Each type of command has a corresponding compiler function on the schema
        // grammar which is used to build the necessary SQL statements to build
        // the blueprint element, so we'll just call that compilers function.
        foreach ($this->commands as $command) {
            $method = 'compile' . ucfirst($command->name);

            if (method_exists($grammar, $method)) {
                if (!is_null($sql = $grammar->$method($this, $command))) {
                    $statements = array_merge($statements, (array)$sql);
                }
            }
        }

        return $statements;
    }

    /**
     * Add the commands that are implied by the blueprint's state.
     *
     * @return void
     */
    protected function addImpliedColumnsCommands()
    {
        if (count($addedColumns = $this->getAddedColumns()) > 0 && !$this->creating()) {
            foreach ($addedColumns as $addedColumn) {
                array_unshift($this->commands, $this->createCommand('addColumn', ['column' => $addedColumn]));
            }
        }

        if (count($changedColumns = $this->getChangedColumns()) > 0 && !$this->creating()) {
            foreach ($changedColumns as $changedColumn) {
                array_unshift($this->commands, $this->createCommand('modifyColumn', ['column' => $changedColumn]));
            }
        }
    }

    /**
     * Add the commands that are implied by the blueprint's state.
     *
     * @return void
     */
    protected function addImpliedCommands()
    {
        $this->addImpliedColumnsCommands();

        $this->addFluentIndexes();
    }

    /**
     * Add the index commands fluently specified on columns.
     *
     * @return void
     */
    protected function addFluentIndexes()
    {
        foreach ($this->columns as $column) {
            if (isset($column->primary) && $column->primary === true) {
                if($this->creating()){
                    $column->setPrimary();
                } else {
                    $this->primary($column->getName());
                }
                continue;
            }
            elseif (isset($column->primary)) {
                if($this->creating()){
                    $column->setPrimary();
                } else {
                    $this->primary($column->getName(),  ...$column->primary);
                }
                continue;
            }

            foreach (['unique', 'index'] as $index) {
                // If the index has been specified on the given column, but is simply equal
                // to "true" (boolean), no name has been specified for this index so the
                // index method can be called without a name and it will generate one.
                if ($column->{$index} === true) {
                    $this->{$index}($column->getName());

                    continue 2;
                }

                // If the index has been specified on the given column, and it has a string
                // value, we'll go ahead and call the index method and pass the name for
                // the index since the developer specified the explicit name for this.
                elseif (isset($column->{$index})) {
                    $this->{$index}($column->getName(), ...$column->{$index});

                    continue 2;
                }
            }
        }
    }

    /**
     * Determine if the blueprint has a create command.
     *
     * @return bool
     */
    protected function creating()
    {
        foreach ($this->commands as $command) {
            if ($command->name == 'create') {
                return true;
            }
        }

        return false;
    }

    /**
     * Indicate that the table needs to be created.
     *
     * @return \Neutrino\Support\Fluent
     */
    public function create()
    {
        return $this->addCommand('create');
    }

    /**
     * Indicate that the table needs to be temporary.
     *
     * @return void
     */
    public function temporary()
    {
        $this->temporary = true;
    }

    /**
     * Indicate that the table should be dropped.
     *
     * @return \Neutrino\Support\Fluent
     */
    public function drop()
    {
        return $this->addCommand('drop');
    }

    /**
     * Indicate that the table should be dropped if it exists.
     *
     * @return \Neutrino\Support\Fluent
     */
    public function dropIfExists()
    {
        return $this->addCommand('dropIfExists');
    }

    /**
     * Indicate that the given columns should be dropped.
     *
     * @param  string $column
     *
     * @return \Neutrino\Support\Fluent
     */
    public function dropColumn($column)
    {
        return $this->addCommand('dropColumn', ['column' => $column]);
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
     * @return \Neutrino\Support\Fluent
     */
    public function renameColumn($from, $to)
    {
        return $this->addCommand('renameColumn', ['from' => $from, 'to' => $to]);
    }

    /**
     * Indicate that the given primary key should be dropped.
     *
     * @param  string|array $index
     *
     * @return \Neutrino\Support\Fluent
     */
    public function dropPrimary($index = null)
    {
        return $this->dropIndexCommand('dropPrimary', 'primary', $index);
    }

    /**
     * Indicate that the given unique key should be dropped.
     *
     * @param  string|array $index
     *
     * @return \Neutrino\Support\Fluent
     */
    public function dropUnique($index)
    {
        return $this->dropIndexCommand('dropUnique', 'unique', $index);
    }

    /**
     * Indicate that the given index should be dropped.
     *
     * @param  string|array $index
     *
     * @return \Neutrino\Support\Fluent
     */
    public function dropIndex($index)
    {
        return $this->dropIndexCommand('dropIndex', 'index', $index);
    }

    /**
     * Indicate that the given foreign key should be dropped.
     *
     * @param  string|array $index
     *
     * @return \Neutrino\Support\Fluent
     */
    public function dropForeign($index)
    {
        return $this->dropReferenceCommand('dropForeign', 'foreign', $index);
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
     * Rename the table to a given name.
     *
     * @param  string $to
     *
     * @return \Neutrino\Support\Fluent
     */
    public function rename($to)
    {
        return $this->addCommand('rename', ['to' => $to]);
    }

    /**
     * Specify the primary key(s) for the table.
     *
     * @param  string|array $columns
     * @param  string       $name
     * @param  string|null  $algorithm
     *
     * @return \Neutrino\Support\Fluent
     */
    public function primary($columns, $name = null, $algorithm = null)
    {
        return $this->addIndexCommand('primary', $columns, $name, $algorithm);
    }

    /**
     * Specify a unique index for the table.
     *
     * @param  string|array $columns
     * @param  string       $name
     * @param  string|null  $algorithm
     *
     * @return \Neutrino\Support\Fluent
     */
    public function unique($columns, $name = null, $algorithm = null)
    {
        return $this->addIndexCommand('unique', $columns, $name, $algorithm);
    }

    /**
     * Specify an index for the table.
     *
     * @param  string|array $columns
     * @param  string       $name
     * @param  string|null  $algorithm
     *
     * @return \Neutrino\Support\Fluent
     */
    public function index($columns, $name = null, $algorithm = null)
    {
        return $this->addIndexCommand('index', $columns, $name, $algorithm);
    }

    /**
     * Specify a foreign key for the table.
     *
     * @param  string|array $columns
     * @param  string       $name
     *
     * @return \Neutrino\Support\Fluent
     */
    public function foreign($columns, $name = null)
    {
        return $this->addReferenceCommand('addForeign', $columns, $name);
    }

    /**
     * Create a new auto-incrementing integer (4-byte) column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Column
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
     * @return \Neutrino\Database\Schema\Column
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
     * @return \Neutrino\Database\Schema\Column
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
     * @return \Neutrino\Database\Schema\Column
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
     * @return \Neutrino\Database\Schema\Column
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
     * @return \Neutrino\Database\Schema\Column
     */
    public function char($column, $length = null)
    {
        $length = $length ?: Builder::$defaultStringLength;

        return $this->addColumn(Column::TYPE_CHAR, $column, ['size' => $length]);
    }

    /**
     * Create a new string column on the table.
     *
     * @param  string $column
     * @param  int    $length
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function string($column, $length = null)
    {
        $length = $length ?: Builder::$defaultStringLength;

        return $this->addColumn(Column::TYPE_VARCHAR, $column, ['size' => $length]);
    }

    /**
     * Create a new text column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function text($column)
    {
        return $this->addColumn(Column::TYPE_TEXT, $column);
    }

    /**
     * Create a new medium text column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function mediumText($column)
    {
        return $this->addColumn(Column::TYPE_MEDIUMBLOB, $column);
    }

    /**
     * Create a new long text column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function longText($column)
    {
        return $this->addColumn(Column::TYPE_LONGBLOB, $column);
    }

    /**
     * Create a new integer (4-byte) column on the table.
     *
     * @param  string $column
     * @param  bool   $autoIncrement
     * @param  bool   $unsigned
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function integer($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addColumn(Column::TYPE_INTEGER, $column, ['autoIncrement' => $autoIncrement, 'unsigned' => $unsigned]);
    }

    /**
     * Create a new tiny integer (1-byte) column on the table.
     *
     * @param  string $column
     * @param  bool   $autoIncrement
     * @param  bool   $unsigned
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function tinyInteger($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addColumn(Column::TYPE_INTEGER, $column, [
            'autoIncrement' => $autoIncrement,
            'unsigned'      => $unsigned,
            'size'        => 1
        ]);
    }

    /**
     * Create a new small integer (2-byte) column on the table.
     *
     * @param  string $column
     * @param  bool   $autoIncrement
     * @param  bool   $unsigned
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function smallInteger($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addColumn(Column::TYPE_INTEGER, $column, [
            'autoIncrement' => $autoIncrement,
            'unsigned'      => $unsigned,
            'size'        => 2
        ]);
    }

    /**
     * Create a new medium integer (3-byte) column on the table.
     *
     * @param  string $column
     * @param  bool   $autoIncrement
     * @param  bool   $unsigned
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function mediumInteger($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addColumn(Column::TYPE_INTEGER, $column, [
            'autoIncrement' => $autoIncrement,
            'unsigned'      => $unsigned,
            'size'        => 3
        ]);
    }

    /**
     * Create a new big integer (8-byte) column on the table.
     *
     * @param  string $column
     * @param  bool   $autoIncrement
     * @param  bool   $unsigned
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function bigInteger($column, $autoIncrement = false, $unsigned = false)
    {
        return $this->addColumn(Column::TYPE_BIGINTEGER, $column, [
            'autoIncrement' => $autoIncrement,
            'unsigned'      => $unsigned
        ]);
    }

    /**
     * Create a new unsigned integer (4-byte) column on the table.
     *
     * @param  string $column
     * @param  bool   $autoIncrement
     *
     * @return \Neutrino\Database\Schema\Column
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
     * @return \Neutrino\Database\Schema\Column
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
     * @return \Neutrino\Database\Schema\Column
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
     * @return \Neutrino\Database\Schema\Column
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
     * @return \Neutrino\Database\Schema\Column
     */
    public function unsignedBigInteger($column, $autoIncrement = false)
    {
        return $this->bigInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new float column on the table.
     *
     * @param  string $column
     * @param  int    $total
     * @param  int    $places
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function float($column, $total = 8, $places = 2)
    {
        return $this->addColumn(Column::TYPE_FLOAT, $column, [
            /*'total'  => $total,
            'places' => $places*/
        ]);
    }

    /**
     * Create a new double column on the table.
     *
     * @param  string   $column
     * @param  int|null $total
     * @param  int|null $places
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function double($column, $total = null, $places = null)
    {
        return $this->addColumn(Column::TYPE_DOUBLE, $column, [/*'total' => $total, 'places' => $places*/]);
    }

    /**
     * Create a new decimal column on the table.
     *
     * @param  string $column
     * @param  int    $total
     * @param  int    $places
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function decimal($column, $total = 8, $places = 2)
    {
        return $this->addColumn(Column::TYPE_DECIMAL, $column, [/*'total' => $total, 'places' => $places*/]);
    }

    /**
     * Create a new boolean column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function boolean($column)
    {
        return $this->addColumn(Column::TYPE_BOOLEAN, $column);
    }

    /**
     * @deprecated
     * @throws \Exception
     *
     * Create a new enum column on the table.
     *
     * @param  string $column
     * @param  array  $allowed
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function enum($column, array $allowed)
    {
        throw new \Exception();

        return $this->addColumn('enum', $column, ['allowed' => $allowed]);
    }

    /**
     * Create a new json column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function json($column)
    {
        return $this->addColumn(Column::TYPE_JSON, $column);
    }

    /**
     * Create a new jsonb column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function jsonb($column)
    {
        return $this->addColumn(Column::TYPE_JSONB, $column);
    }

    /**
     * Create a new date column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function date($column)
    {
        return $this->addColumn(Column::TYPE_DATE, $column);
    }

    /**
     * Create a new date-time column on the table.
     *
     * @param  string $column
     * @param  int    $precision
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function dateTime($column, $precision = 0)
    {
        return $this->addColumn(Column::TYPE_DATETIME, $column, [/*'precision' => $precision*/]);
    }

    /**
     * @deprecated
     * @throws \Exception
     *
     * Create a new date-time column (with time zone) on the table.
     *
     * @param  string $column
     * @param  int    $precision
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function dateTimeTz($column, $precision = 0)
    {
        throw new \Exception();

        return $this->addColumn(Column::TYPE_DATETIME, $column, [/*'precision' => $precision*/]);
    }

    /**
     * @deprecated
     * @throws \Exception
     *
     * Create a new time column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function time($column)
    {
        throw new \Exception();

        return $this->addColumn('time', $column);
    }

    /**
     * @deprecated
     * @throws \Exception
     *
     * Create a new time column (with time zone) on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function timeTz($column)
    {
        throw new \Exception();

        return $this->addColumn('timeTz', $column);
    }

    /**
     * Create a new timestamp column on the table.
     *
     * @param  string $column
     * @param  int    $precision
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function timestamp($column, $precision = 0)
    {
        return $this->addColumn(Column::TYPE_TIMESTAMP, $column, [/*'precision' => $precision*/]);
    }

    /**
     * @deprecated
     * @throws \Exception
     *
     * Create a new timestamp (with time zone) column on the table.
     *
     * @param  string $column
     * @param  int    $precision
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function timestampTz($column, $precision = 0)
    {
        throw new \Exception();

        return $this->addColumn('timestampTz', $column, ['precision' => $precision]);
    }

    /**
     * Add nullable creation and update timestamps to the table.
     *
     * @param  int $precision
     *
     * @return void
     */
    public function timestamps($precision = 0)
    {
        $this->timestamp('created_at', $precision)->setNullable();

        $this->timestamp('updated_at', $precision)->setNullable();
    }

    /**
     * Add nullable creation and update timestamps to the table.
     *
     * Alias for self::timestamps().
     *
     * @param  int $precision
     *
     * @return void
     */
    public function nullableTimestamps($precision = 0)
    {
        $this->timestamps($precision);
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
        $this->timestampTz('created_at', $precision)->setNullable();

        $this->timestampTz('updated_at', $precision)->setNullable();
    }

    /**
     * Add a "deleted at" timestamp for the table.
     *
     * @param  string $column
     * @param  int    $precision
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function softDeletes($column = 'deleted_at', $precision = 0)
    {
        return $this->timestamp($column, $precision)->setNullable();
    }

    /**
     * Add a "deleted at" timestampTz for the table.
     *
     * @param  int $precision
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function softDeletesTz($precision = 0)
    {
        return $this->timestampTz('deleted_at', $precision)->setNullable();
    }

    /**
     * Create a new binary column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function binary($column)
    {
        return $this->addColumn(Column::TYPE_BLOB, $column);
    }

    /**
     * Create a new uuid column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function uuid($column)
    {
        /* @todo Uuid behavior */
        return $this->char($column, 36);
    }

    /**
     * Create a new IP address column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function ipAddress($column)
    {
        /* @todo IpAddress behavior */
        return $this->string($column, 45);
    }

    /**
     * Create a new MAC address column on the table.
     *
     * @param  string $column
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function macAddress($column)
    {
        /* @todo MacAddress behavior */
        return $this->string($column, 17);
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
        $this->unsignedInteger("{$name}_id")->setNullable();

        $this->string("{$name}_type")->setNullable();

        $this->index(["{$name}_id", "{$name}_type"], $indexName);
    }

    /**
     * Adds the `remember_token` column to the table.
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function rememberToken()
    {
        return $this->string('remember_token', 100)->setNullable();
    }

    /**
     * Add a new index command to the blueprint.
     *
     * @param  string          $type
     * @param  string|string[] $columns
     * @param  string          $index
     * @param  string|null     $algorithm
     *
     * @return \Neutrino\Support\Fluent
     */
    protected function addIndexCommand($type, $columns, $index, $algorithm = null)
    {
        $columns = (array)$columns;

        // If no name was specified for this index, we will create one using a basic
        // convention of the table name, followed by the columns, followed by an
        // index type, such as primary or index, which makes the index unique.
        $index = $index ?: $this->createIndexName($type, $columns);

        $this->indexes[] = $index = new Index($index, $columns, $type);

        return $this->addCommand(
            'add' . Str::capitalize($type), ['index' => $index, 'type' => $type, 'columns' => $columns, 'algorithm' => $algorithm]
        );
    }

    /**
     * Create a new drop index command on the blueprint.
     *
     * @param  string       $command
     * @param  string       $type
     * @param  string|array $index
     *
     * @return \Neutrino\Support\Fluent
     */
    protected function dropIndexCommand($command, $type, $index)
    {
        $columns = [];

        // If the given "index" is actually an array of columns, the developer means
        // to drop an index merely by specifying the columns involved without the
        // conventional name, so we will build the index name from the columns.
        if (is_array($index)) {
            $index = $this->createIndexName($type, $columns = $index);
        }

        foreach ($this->indexes as $key => $_index) {
            if ($_index->getName() === $index) {
                unset($this->indexes[$key]);
                break;
            }
        }

        return $this->addCommand(
            $command, ['index' => $index, 'type' => $type, 'columns' => $columns]
        );
    }

    protected function addReferenceCommand($type, $columns, $name)
    {
        $columns = (array)$columns;

        // If no name was specified for this index, we will create one using a basic
        // convention of the table name, followed by the columns, followed by an
        // index type, such as primary or index, which makes the index unique.
        $name = $name ?: $this->createIndexName($type, $columns);

        $this->references[] = $reference = new Reference($name, [
            'columns' => $columns
        ]);

        return $this->addCommand(
            $type, ['reference' => $reference, 'type' => $type, 'columns' => $columns]
        );
    }

    /**
     * Create a new drop reference command on the blueprint.
     *
     * @param string       $command
     * @param string       $type
     * @param string|array $reference
     *
     * @return \Neutrino\Support\Fluent
     */
    protected function dropReferenceCommand($command, $type, $reference)
    {
        $columns = [];

        // If the given "index" is actually an array of columns, the developer means
        // to drop an index merely by specifying the columns involved without the
        // conventional name, so we will build the index name from the columns.
        if (is_array($reference)) {
            $reference = $this->createIndexName($type, $columns = $reference);
        }

        foreach ($this->references as $key => $_index) {
            if ($_index->getName() === $reference) {
                unset($this->references[$key]);
                break;
            }
        }

        return $this->addCommand(
            $command, ['reference' => $reference, 'type' => $type, 'columns' => $columns]
        );
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
        $index = strtolower($this->table . '_' . implode('_', $columns) . '_' . $type);

        return str_replace(['-', '.'], '_', $index);
    }

    /**
     * Add a new column to the blueprint.
     *
     * @param  int    $type
     * @param  string $name
     * @param  array  $parameters
     *
     * @return \Neutrino\Database\Schema\Column
     */
    public function addColumn($type, $name, array $parameters = [])
    {
        return $this->columns[] = new Column(
            $name,
            array_merge([
                "type" => $type,
            ], $parameters)
        );
    }

    /**
     * Remove a column from the schema blueprint.
     *
     * @param  string $name
     *
     * @return $this
     */
    public function removeColumn($name)
    {
        foreach ($this->columns as $key => $column) {
            if ($column->getName() === $name) {
                unset($this->indexes[$key]);
                break;
            }
        }

        return $this;
    }

    /**
     * Add a new command to the blueprint.
     *
     * @param  string $name
     * @param  array  $parameters
     *
     * @return \Neutrino\Support\Fluent
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
     * @return \Neutrino\Support\Fluent
     */
    protected function createCommand($name, array $parameters = [])
    {
        return new Fluent(array_merge(['name' => $name], $parameters));
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
     * Get the columns on the blueprint that should be added.
     *
     * @return array
     */
    public function getAddedColumns()
    {
        return array_filter($this->columns, function ($column) {
            return !$column->change;
        });
    }

    /**
     * Get the columns on the blueprint that should be changed.
     *
     * @return array
     */
    public function getChangedColumns()
    {
        return array_filter($this->columns, function ($column) {
            return (bool)$column->change;
        });
    }
}
<?php

declare(strict_types=1);

namespace Neutrino\Database\Schema;

use Neutrino\Database\Schema\Exception\CommandException;
use Neutrino\Database\Schema\Exception\UnknownCommandException;
use Phalcon\Db\Adapter\AdapterInterface;
use Phalcon\Db\ColumnInterface;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use RuntimeException;
use Throwable;

/**
 * The columns, indexes, foreign keys and commands of a table, run by {@see Builder}.
 *
 * Creating a table: the columns, the primary key and the foreign keys are created with the table, the other
 * indexes after it. Updating a table: the commands (`dropColumn()`, `renameColumn()`, `dropIndex()`…) run first,
 * in their order, then each column is added, or modified when it exists, then the indexes and foreign keys.
 */
class Blueprint
{
    protected ?string $schema = null;

    /** @var array<string, Definition> */
    protected array $columns = [];

    /** @var list<Definition> */
    protected array $indexes = [];

    /** @var list<Definition> */
    protected array $references = [];

    /** @var list<Definition> */
    protected array $commands = [];

    /** @var array<string, mixed> */
    protected array $options = [];

    protected ?string $action = null;

    public function __construct(protected string $table) {}

    /**
     * Runs the blueprint on the connection.
     *
     * @throws CommandException
     */
    public function build(AdapterInterface $db, Grammar $grammar): void
    {
        match ($this->action) {
            'create'       => $this->buildCreate($db, $grammar),
            'update'       => $this->buildUpdate($db, $grammar),
            'drop'         => $this->run($this->createCommand('drop'), fn() => $db->dropTable($this->table, $this->schema, false)),
            'dropIfExists' => $this->run($this->createCommand('drop'), fn() => $db->dropTable($this->table, $this->schema, true)),
            'raw'          => $this->runCommands($db, $grammar),
            default        => throw new RuntimeException("The blueprint of the table \"{$this->table}\" has no action (create, update, drop…)."),
        };
    }

    protected function buildCreate(AdapterInterface $db, Grammar $grammar): void
    {
        [$primary, $indexes, $references] = $this->keys();
        $columns = [];
        $definition = [];

        foreach ($this->columns as $name => $column) {
            $isPrimary = in_array($name, $primary, true) && (count($primary) === 1 || !$grammar->primaryAsIndex());
            $columns[] = $this->toColumn($column, $grammar, $isPrimary);
        }
        $definition['columns'] = $columns;

        if (count($primary) > 1 && $grammar->primaryAsIndex()) {
            $definition['indexes'] = [new Index('PRIMARY', $primary, 'PRIMARY')];
        }
        if ($this->options !== []) {
            $definition['options'] = $this->options;
        }

        $this->run($this->createCommand('create'), function () use ($db, $definition, $references): void {
            if ($references !== []) {
                $definition['references'] = array_map($this->toReference(...), $references);
            }

            $db->createTable($this->table, $this->schema ?? '', $definition);
        });

        // Created apart: the dialects do not all create the indexes declared with the table (SQLite, PostgreSQL).
        foreach ($indexes as $index) {
            $this->run($this->createCommand('addIndex', ['index' => $index]), fn() => $db->addIndex($this->table, $this->schema ?? '', $this->toIndex($index)));
        }

        $this->runCommands($db, $grammar);
    }

    protected function buildUpdate(AdapterInterface $db, Grammar $grammar): void
    {
        // The table is renamed last: the other changes target its current name.
        $this->runCommands($db, $grammar, false);

        [$primary, $indexes, $references] = $this->keys();
        $existing = [];

        if ($this->columns !== []) {
            foreach ($db->describeColumns($this->table, $this->schema) as $column) {
                $existing[$column->getName()] = $column;
            }
        }

        // A new primary column (`increments()`) is added with its key; an existing primary key is kept.
        $newPrimary = count($primary) === 1 && isset($this->columns[$primary[0]]) && !isset($existing[$primary[0]]) ? $primary[0] : null;
        $keptPrimary = $primary !== [] && array_filter($primary, static fn(string $name): bool => !isset($existing[$name]) || !$existing[$name]->isPrimary()) === [];

        foreach ($this->columns as $name => $column) {
            $command = $this->createCommand(isset($existing[$name]) ? 'modifyColumn' : 'addColumn', ['column' => $column]);
            $definition = $this->toColumn($column, $grammar, $name === $newPrimary);

            $this->run($command, isset($existing[$name])
                ? fn() => $this->modifyColumn($db, $grammar, $definition, $existing[$name])
                : fn() => $db->execute($grammar->addColumn($this->table, $definition, $this->schema ?? '')));
        }

        if ($primary !== [] && $newPrimary === null && !$keptPrimary) {
            $this->run($this->createCommand('addPrimary', ['columns' => $primary]), fn() => $db->execute($grammar->addPrimary($this->table, $primary, $this->schema ?? '')));
        }
        foreach ($indexes as $index) {
            $this->run($this->createCommand('addIndex', ['index' => $index]), fn() => $db->addIndex($this->table, $this->schema ?? '', $this->toIndex($index)));
        }
        foreach ($references as $reference) {
            $this->run($this->createCommand('addForeign', ['reference' => $reference]), fn() => $db->addForeignKey($this->table, $this->schema ?? '', $this->toReference($reference)));
        }

        $this->runCommands($db, $grammar, true);
    }

    protected function modifyColumn(AdapterInterface $db, Grammar $grammar, ColumnInterface $column, ColumnInterface $current): void
    {
        $sql = $grammar->modifyColumn($this->table, $column, $current, $this->schema ?? '');

        if ($sql === null) {
            $db->modifyColumn($this->table, $this->schema ?? '', $column, $current);
        } elseif ($sql !== '') {
            $db->execute($sql);
        }
    }

    /**
     * Runs the commands (`dropColumn()`, `renameColumn()`, `sql()`…), in their order.
     *
     * @param bool|null $renames Only the renaming of the table (`true`), all but it (`false`), all (`null`)
     */
    protected function runCommands(AdapterInterface $db, Grammar $grammar, ?bool $renames = null): void
    {
        $table = $this->table;
        $schema = $this->schema ?? '';

        foreach ($this->commands as $command) {
            if ($renames !== null && ($command->get('name') === 'rename') !== $renames) {
                continue;
            }

            $this->run($command, match ($command->get('name')) {
                'dropColumn'   => fn() => array_map(fn(string $column): bool => $db->dropColumn($table, $schema, $column), self::names($command->get('columns'))),
                'renameColumn' => fn() => $db->execute($grammar->renameColumn($table, self::name($command->get('from')), self::name($command->get('to')), $schema)),
                'dropPrimary'  => fn() => $db->execute($grammar->dropPrimary($table, $schema)),
                'dropIndex'    => fn() => array_map(fn(string $index): bool => $db->dropIndex($table, $schema, $index), self::names($command->get('index'))),
                'dropForeign'  => fn() => array_map(fn(string $key): bool => $db->dropForeignKey($table, $schema, $key), self::names($command->get('reference'))),
                'rename'       => fn() => $db->execute($grammar->renameTable($table, self::name($command->get('to')), $schema)),
                'sql'          => fn() => $db->execute(self::name($command->get('sql'))),
                default        => throw new UnknownCommandException($command, null, $table),
            });
        }
    }

    /**
     * Runs a command, the errors becoming {@see CommandException}.
     */
    protected function run(Definition $command, \Closure $callback): void
    {
        try {
            $callback();
        } catch (CommandException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new CommandException($command, $e, $this->table);
        }
    }

    /**
     * The primary key, the indexes and the foreign keys, from the blueprint and from the column modifiers
     * (`->primary()`, `->unique()`, `->index()`, `->foreign()`).
     *
     * @return array{list<string>, list<Definition>, list<Definition>}
     */
    protected function keys(): array
    {
        $primary = [];
        $indexes = [];
        $references = $this->references;

        foreach ($this->columns as $name => $column) {
            if ($column->get('primary')) {
                $primary[] = $name;
            }
            foreach (['unique', 'index'] as $type) {
                if ($modifier = $column->get($type)) {
                    $indexes[] = $this->createIndex($type, [$name], is_string($modifier) ? $modifier : null);
                }
            }
            if ($column->get('foreign')) {
                $references[] = new Definition([
                    'name'       => null,
                    'columns'    => [$name],
                    'on'         => $column->get('on'),
                    'references' => $column->get('references'),
                    'onDelete'   => $column->get('onDelete'),
                    'onUpdate'   => $column->get('onUpdate'),
                ]);
            }
        }

        foreach ($this->indexes as $index) {
            if ($index->get('type') === 'PRIMARY') {
                array_push($primary, ...self::names($index->get('columns')));
            } else {
                $indexes[] = $index;
            }
        }

        return [array_values(array_unique($primary)), $indexes, $references];
    }

    /**
     * The Phalcon column of a column of the blueprint, without its keys (created apart).
     */
    protected function toColumn(Definition $column, Grammar $grammar, bool $primary): ColumnInterface
    {
        $attributes = $column->getAttributes();
        unset($attributes['unique'], $attributes['index'], $attributes['primary']);

        if ($column->get('foreign')) {
            unset($attributes['foreign'], $attributes['on'], $attributes['references'], $attributes['onDelete'], $attributes['onUpdate']);
        }
        if ($primary) {
            $attributes['primary'] = true;
        }

        return $grammar->column(new Definition($attributes));
    }

    protected function toIndex(Definition $index): Index
    {
        $type = strtoupper(self::name($index->get('type')));
        $columns = self::names($index->get('columns'));
        $name = $index->get('name');

        return new Index(
            is_string($name) && $name !== '' ? $name : $this->createIndexName($type, $columns),
            $columns,
            $type === 'INDEX' ? '' : $type,
        );
    }

    protected function toReference(Definition $reference): Reference
    {
        $columns = self::names($reference->get('columns'));
        $on = $reference->get('on');
        $references = self::names($reference->get('references'));

        if (!is_string($on) || $on === '' || $references === []) {
            throw new RuntimeException('the foreign key on ' . implode(', ', $columns) . ' needs ->references(…)->on(…).');
        }

        $name = $reference->get('name');
        $definition = [
            'columns'           => $columns,
            'referencedTable'   => $on,
            'referencedColumns' => $references,
        ];

        foreach (['onDelete', 'onUpdate'] as $action) {
            if (is_string($value = $reference->get($action)) && $value !== '') {
                $definition[$action] = strtoupper($value);
            }
        }

        return new Reference(is_string($name) && $name !== '' ? $name : $this->createReferenceName($columns, $on, $references), $definition);
    }

    /**
     * Indicate that the table needs to be temporary.
     */
    public function temporary(): static
    {
        return $this->option('temporary', true);
    }

    /**
     * Indicate that the table needs to be created.
     */
    public function create(): static
    {
        $this->action = __FUNCTION__;

        return $this;
    }

    /**
     * Indicate that the table needs to be updated.
     */
    public function update(): static
    {
        $this->action = __FUNCTION__;

        return $this;
    }

    /**
     * Indicate that the table should be dropped.
     */
    public function drop(): static
    {
        $this->action = __FUNCTION__;

        return $this;
    }

    /**
     * Indicate that the table should be dropped if it exists.
     */
    public function dropIfExists(): static
    {
        $this->action = __FUNCTION__;

        return $this;
    }

    /**
     * Indicate that the blueprint only runs its commands.
     */
    public function raw(): static
    {
        $this->action = __FUNCTION__;

        return $this;
    }

    /**
     * Indicate that the given columns should be dropped.
     *
     * @param string|list<string> $columns
     */
    public function dropColumn(string|array $columns): Definition
    {
        return $this->addCommand(__FUNCTION__, ['columns' => (array) $columns]);
    }

    /**
     * Indicate that the given columns should be dropped.
     *
     * @param list<string> $columns
     */
    public function dropColumns(array $columns): void
    {
        $this->dropColumn($columns);
    }

    /**
     * Indicate that the given column should be renamed.
     */
    public function renameColumn(string $from, string $to): Definition
    {
        return $this->addCommand(__FUNCTION__, ['from' => $from, 'to' => $to]);
    }

    /**
     * Indicate that the primary key should be dropped.
     */
    public function dropPrimary(): Definition
    {
        return $this->addCommand(__FUNCTION__);
    }

    /**
     * Indicate that the given unique keys should be dropped.
     *
     * @param string|list<string> $index Names of the indexes
     */
    public function dropUnique(string|array $index): Definition
    {
        return $this->dropIndex($index);
    }

    /**
     * Indicate that the given indexes should be dropped.
     *
     * @param string|list<string> $index Names of the indexes
     */
    public function dropIndex(string|array $index): Definition
    {
        return $this->addCommand('dropIndex', ['index' => (array) $index]);
    }

    /**
     * Indicate that the given foreign keys should be dropped.
     *
     * @param string|list<string> $reference Names of the foreign keys
     */
    public function dropForeign(string|array $reference): Definition
    {
        return $this->addCommand(__FUNCTION__, ['reference' => (array) $reference]);
    }

    /**
     * Indicate that the timestamp columns should be dropped.
     */
    public function dropTimestamps(): void
    {
        $this->dropColumns(['created_at', 'updated_at']);
    }

    /**
     * Indicate that the timestamp columns should be dropped.
     */
    public function dropTimestampsTz(): void
    {
        $this->dropTimestamps();
    }

    /**
     * Indicate that the soft delete column should be dropped.
     */
    public function dropSoftDeletes(string $column = 'deleted_at'): void
    {
        $this->dropColumn($column);
    }

    /**
     * Indicate that the soft delete column should be dropped.
     */
    public function dropSoftDeletesTz(string $column = 'deleted_at'): void
    {
        $this->dropSoftDeletes($column);
    }

    /**
     * Indicate that the remember token column should be dropped.
     */
    public function dropRememberToken(): void
    {
        $this->dropColumn('remember_token');
    }

    /**
     * The schema of the table (PostgreSQL), or the database (MySQL).
     */
    public function schema(string $schema): static
    {
        $this->schema = $schema;

        return $this;
    }

    /**
     * Rename the table to a given name.
     */
    public function rename(string $to): Definition
    {
        return $this->addCommand(__FUNCTION__, ['to' => $to]);
    }

    /**
     * Specify the primary key(s) for the table.
     *
     * @param string|list<string> $columns
     */
    public function primary(string|array $columns, ?string $name = null): Definition
    {
        return $this->addIndex(__FUNCTION__, $columns, $name);
    }

    /**
     * Specify a unique index for the table.
     *
     * @param string|list<string> $columns
     */
    public function unique(string|array $columns, ?string $name = null): Definition
    {
        return $this->addIndex(__FUNCTION__, $columns, $name);
    }

    /**
     * Specify an index for the table.
     *
     * @param string|list<string> $columns
     * @param string              $type    `index`, or a type of the database (`fulltext`, `spatial` on MySQL)
     */
    public function index(string|array $columns, ?string $name = null, string $type = 'index'): Definition
    {
        return $this->addIndex($type, $columns, $name);
    }

    /**
     * Specify a foreign key for the table: `->foreign('user_id')->references('id')->on('users')`.
     *
     * @param string|list<string> $columns
     */
    public function foreign(string|array $columns, ?string $name = null): Definition
    {
        return $this->references[] = new Definition(['name' => $name, 'columns' => (array) $columns, 'type' => 'foreign']);
    }

    /**
     * Create a new auto-incrementing integer (4-byte) column on the table.
     */
    public function increments(string $column): Definition
    {
        return $this->unsignedInteger($column, true);
    }

    /**
     * Create a new auto-incrementing tiny integer (1-byte) column on the table.
     */
    public function tinyIncrements(string $column): Definition
    {
        return $this->unsignedTinyInteger($column, true);
    }

    /**
     * Create a new auto-incrementing small integer (2-byte) column on the table.
     */
    public function smallIncrements(string $column): Definition
    {
        return $this->unsignedSmallInteger($column, true);
    }

    /**
     * Create a new auto-incrementing medium integer (3-byte) column on the table.
     */
    public function mediumIncrements(string $column): Definition
    {
        return $this->unsignedMediumInteger($column, true);
    }

    /**
     * Create a new auto-incrementing big integer (8-byte) column on the table.
     */
    public function bigIncrements(string $column): Definition
    {
        return $this->unsignedBigInteger($column, true);
    }

    /**
     * Create a new char column on the table.
     */
    public function char(string $column, ?int $length = null): Definition
    {
        return $this->addColumn(__FUNCTION__, $column, ['size' => $length ?: Builder::$defaultStringLength]);
    }

    /**
     * Create a new string column on the table.
     */
    public function string(string $column, ?int $length = null): Definition
    {
        return $this->addColumn(__FUNCTION__, $column, ['size' => $length ?: Builder::$defaultStringLength]);
    }

    /**
     * Create a new text column on the table.
     */
    public function text(string $column): Definition
    {
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * Create a new medium text column on the table.
     */
    public function mediumText(string $column): Definition
    {
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * Create a new long text column on the table.
     */
    public function longText(string $column): Definition
    {
        return $this->addColumn(__FUNCTION__, $column);
    }

    protected function addInteger(string $type, string $column, bool $autoIncrement = false, bool $unsigned = false): Definition
    {
        $definition = $this->addColumn($type, $column, ['autoIncrement' => $autoIncrement, 'unsigned' => $unsigned]);

        if ($autoIncrement) {
            $definition->primary();
        }

        return $definition;
    }

    /**
     * Create a new integer (4-byte) column on the table.
     */
    public function integer(string $column, bool $autoIncrement = false, bool $unsigned = false): Definition
    {
        return $this->addInteger(__FUNCTION__, $column, $autoIncrement, $unsigned);
    }

    /**
     * Create a new tiny integer (1-byte) column on the table.
     */
    public function tinyInteger(string $column, bool $autoIncrement = false, bool $unsigned = false): Definition
    {
        return $this->addInteger(__FUNCTION__, $column, $autoIncrement, $unsigned);
    }

    /**
     * Create a new small integer (2-byte) column on the table.
     */
    public function smallInteger(string $column, bool $autoIncrement = false, bool $unsigned = false): Definition
    {
        return $this->addInteger(__FUNCTION__, $column, $autoIncrement, $unsigned);
    }

    /**
     * Create a new medium integer (3-byte) column on the table.
     */
    public function mediumInteger(string $column, bool $autoIncrement = false, bool $unsigned = false): Definition
    {
        return $this->addInteger(__FUNCTION__, $column, $autoIncrement, $unsigned);
    }

    /**
     * Create a new big integer (8-byte) column on the table.
     */
    public function bigInteger(string $column, bool $autoIncrement = false, bool $unsigned = false): Definition
    {
        return $this->addInteger(__FUNCTION__, $column, $autoIncrement, $unsigned);
    }

    /**
     * Create a new unsigned integer (4-byte) column on the table.
     */
    public function unsignedInteger(string $column, bool $autoIncrement = false): Definition
    {
        return $this->integer($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned tiny integer (1-byte) column on the table.
     */
    public function unsignedTinyInteger(string $column, bool $autoIncrement = false): Definition
    {
        return $this->tinyInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned small integer (2-byte) column on the table.
     */
    public function unsignedSmallInteger(string $column, bool $autoIncrement = false): Definition
    {
        return $this->smallInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned medium integer (3-byte) column on the table.
     */
    public function unsignedMediumInteger(string $column, bool $autoIncrement = false): Definition
    {
        return $this->mediumInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned big integer (8-byte) column on the table.
     */
    public function unsignedBigInteger(string $column, bool $autoIncrement = false): Definition
    {
        return $this->bigInteger($column, $autoIncrement, true);
    }

    /**
     * Create a new float column on the table.
     *
     * @param int|null $scale     Digits after the decimal point
     * @param int|null $precision Total number of digits
     */
    public function float(string $column, ?int $scale = null, ?int $precision = null): Definition
    {
        return $this->addColumn(__FUNCTION__, $column, ['scale' => $scale, 'size' => $precision]);
    }

    /**
     * Create a new double column on the table.
     *
     * @param int|null $scale     Digits after the decimal point
     * @param int|null $precision Total number of digits
     */
    public function double(string $column, ?int $scale = null, ?int $precision = null): Definition
    {
        return $this->addColumn(__FUNCTION__, $column, ['scale' => $scale, 'size' => $precision]);
    }

    /**
     * Create a new decimal column on the table: `DECIMAL(10, 0)` by default.
     *
     * @param int|null $scale     Digits after the decimal point
     * @param int|null $precision Total number of digits
     */
    public function decimal(string $column, ?int $scale = null, ?int $precision = null): Definition
    {
        return $this->addColumn(__FUNCTION__, $column, ['scale' => $scale, 'size' => $precision]);
    }

    /**
     * Create a new boolean column on the table.
     */
    public function boolean(string $column): Definition
    {
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * Create a new enum column on the table (a `CHECK` constraint on PostgreSQL and SQLite).
     *
     * @param list<string> $allowed
     */
    public function enum(string $column, array $allowed): Definition
    {
        return $this->addColumn(__FUNCTION__, $column, ['values' => $allowed]);
    }

    /**
     * Create a new json column on the table.
     */
    public function json(string $column): Definition
    {
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * Create a new jsonb column on the table.
     */
    public function jsonb(string $column): Definition
    {
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * Create a new date column on the table.
     */
    public function date(string $column): Definition
    {
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * Create a new date-time column on the table.
     */
    public function dateTime(string $column, int $precision = 0): Definition
    {
        return $this->addColumn(__FUNCTION__, $column, ['precision' => $precision]);
    }

    /**
     * Create a new date-time column (with time zone) on the table.
     */
    public function dateTimeTz(string $column, int $precision = 0): Definition
    {
        return $this->addColumn(__FUNCTION__, $column, ['precision' => $precision]);
    }

    /**
     * Create a new time column on the table.
     */
    public function time(string $column, int $precision = 0): Definition
    {
        return $this->addColumn(__FUNCTION__, $column, ['precision' => $precision]);
    }

    /**
     * Create a new time column (with time zone) on the table.
     */
    public function timeTz(string $column, int $precision = 0): Definition
    {
        return $this->addColumn(__FUNCTION__, $column, ['precision' => $precision]);
    }

    /**
     * Create a new timestamp column on the table.
     */
    public function timestamp(string $column, int $precision = 0): Definition
    {
        return $this->addColumn(__FUNCTION__, $column, ['precision' => $precision]);
    }

    /**
     * Create a new timestamp (with time zone) column on the table.
     */
    public function timestampTz(string $column, int $precision = 0): Definition
    {
        return $this->addColumn(__FUNCTION__, $column, ['precision' => $precision]);
    }

    /**
     * Add creation and update timestamps to the table (`ON UPDATE CURRENT_TIMESTAMP` on MySQL).
     */
    public function timestamps(int $precision = 0): void
    {
        $this->timestamp('created_at', $precision)->default('CURRENT_TIMESTAMP');

        $this->timestamp('updated_at', $precision)->default('CURRENT_TIMESTAMP')->onUpdate('CURRENT_TIMESTAMP');
    }

    /**
     * Add nullable creation and update timestamps to the table.
     */
    public function nullableTimestamps(int $precision = 0): void
    {
        $this->timestamps($precision);

        $this->columns['created_at']->nullable();
        $this->columns['updated_at']->nullable();
    }

    /**
     * Add creation and update timestampTz columns to the table.
     */
    public function timestampsTz(int $precision = 0): void
    {
        $this->timestampTz('created_at', $precision)->default('CURRENT_TIMESTAMP');

        $this->timestampTz('updated_at', $precision)->default('CURRENT_TIMESTAMP')->onUpdate('CURRENT_TIMESTAMP');
    }

    /**
     * Add nullable creation and update timestampTz to the table.
     */
    public function nullableTimestampsTz(int $precision = 0): void
    {
        $this->timestampsTz($precision);

        $this->columns['created_at']->nullable();
        $this->columns['updated_at']->nullable();
    }

    /**
     * Add a "deleted at" timestamp for the table.
     */
    public function softDeletes(string $column = 'deleted_at', int $precision = 0): Definition
    {
        return $this->timestamp($column, $precision)->nullable();
    }

    /**
     * Add a "deleted at" timestampTz for the table.
     */
    public function softDeletesTz(string $column = 'deleted_at', int $precision = 0): Definition
    {
        return $this->timestampTz($column, $precision)->nullable();
    }

    /**
     * Create a new binary column on the table.
     */
    public function binary(string $column): Definition
    {
        return $this->blob($column);
    }

    /**
     * Create a new blob column on the table.
     */
    public function blob(string $column): Definition
    {
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * Create a new tiny blob column on the table.
     */
    public function tinyBlob(string $column): Definition
    {
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * Create a new medium blob column on the table.
     */
    public function mediumBlob(string $column): Definition
    {
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * Create a new long blob column on the table.
     */
    public function longBlob(string $column): Definition
    {
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * Create a new uuid column on the table (`UUID` on PostgreSQL, `CHAR(36)` otherwise).
     */
    public function uuid(string $column): Definition
    {
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * Create a new IP address column on the table (`INET` on PostgreSQL, `VARCHAR(45)` otherwise).
     */
    public function ipAddress(string $column): Definition
    {
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * Create a new MAC address column on the table (`MACADDR` on PostgreSQL, `VARCHAR(17)` otherwise).
     */
    public function macAddress(string $column): Definition
    {
        return $this->addColumn(__FUNCTION__, $column);
    }

    /**
     * Add the proper columns for a polymorphic table.
     */
    public function morphs(string $name, ?string $indexName = null): void
    {
        $this->unsignedInteger("{$name}_id");

        $this->string("{$name}_type");

        $this->index(["{$name}_id", "{$name}_type"], $indexName);
    }

    /**
     * Add nullable columns for a polymorphic table.
     */
    public function nullableMorphs(string $name, ?string $indexName = null): void
    {
        $this->unsignedInteger("{$name}_id")->nullable();

        $this->string("{$name}_type")->nullable();

        $this->index(["{$name}_id", "{$name}_type"], $indexName);
    }

    /**
     * Adds the `remember_token` column to the table.
     */
    public function rememberToken(): Definition
    {
        return $this->string('remember_token', 100)->nullable();
    }

    /**
     * Add raw sql to execute.
     */
    public function sql(string $sql): Definition
    {
        return $this->addCommand(__FUNCTION__, ['sql' => $sql]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * A table option: `temporary`, or a MySQL one (`ENGINE`, `AUTO_INCREMENT`, `TABLE_COLLATION`).
     */
    public function option(string $name, mixed $value): static
    {
        $this->options[strtolower($name) === 'temporary' ? 'temporary' : strtoupper($name)] = $value;

        return $this;
    }

    /**
     * Get the table the blueprint describes.
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get the columns on the blueprint.
     *
     * @return array<string, Definition>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Get the commands on the blueprint.
     *
     * @return list<Definition>
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Get the indexes on the blueprint.
     *
     * @return list<Definition>
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    /**
     * Get the foreign keys on the blueprint.
     *
     * @return list<Definition>
     */
    public function getReferences(): array
    {
        return $this->references;
    }

    /**
     * Add a new column to the blueprint.
     *
     * @param array<string, mixed> $parameters
     */
    protected function addColumn(string $type, string $name, array $parameters = []): Definition
    {
        return $this->columns[$name] = new Definition(['name' => $name, 'type' => $type] + array_filter($parameters, static fn(mixed $value): bool => $value !== null));
    }

    /**
     * Add a new command to the blueprint.
     *
     * @param array<string, mixed> $parameters
     */
    protected function addCommand(string $name, array $parameters = []): Definition
    {
        return $this->commands[] = $this->createCommand($name, $parameters);
    }

    /**
     * Create a new command.
     *
     * @param array<string, mixed> $parameters
     */
    protected function createCommand(string $name, array $parameters = []): Definition
    {
        return new Definition(['name' => $name] + $parameters);
    }

    /**
     * Add a new index to the blueprint.
     *
     * @param string|list<string> $columns
     */
    protected function addIndex(string $type, string|array $columns, ?string $name = null): Definition
    {
        return $this->indexes[] = $this->createIndex($type, (array) $columns, $name);
    }

    /**
     * @param list<string> $columns
     */
    protected function createIndex(string $type, array $columns, ?string $name): Definition
    {
        return new Definition(['name' => $name ?: $this->createIndexName($type, $columns), 'columns' => $columns, 'type' => strtoupper($type)]);
    }

    /**
     * Default name of an index: `<table>_<columns>_<type>`.
     *
     * @param list<string> $columns
     */
    protected function createIndexName(string $type, array $columns): string
    {
        $index = strtolower(implode('_', array_filter([$this->table, implode('_', $columns), $type])));

        return trim(str_replace(['-', '.'], '_', $index), '_');
    }

    /**
     * Default name of a foreign key: `<table>_<columns>_foreign_<referenced table>_<referenced columns>`.
     *
     * @param list<string> $columns
     * @param list<string> $references
     */
    protected function createReferenceName(array $columns, string $on, array $references): string
    {
        $parts = array_map(
            static fn(string $value): string => trim(str_replace(['-', '.'], '_', $value), '_'),
            [$this->table, implode('_', $columns), 'foreign', $on, implode('_', $references)],
        );

        return strtolower(trim(implode('_', array_filter($parts)), '_'));
    }

    private static function name(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * @return list<string>
     */
    private static function names(mixed $value): array
    {
        return array_values(array_map(self::name(...), (array) $value));
    }
}

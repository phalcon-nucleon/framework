<?php

namespace Neutrino\Database\Schema\Grammars;

use Neutrino\Database\Schema\Blueprint;
use Neutrino\Database\Schema\Grammar as BaseGrammar;
use Neutrino\Support\Fluent;

abstract class Grammar extends BaseGrammar
{
    /**
     * If this Grammar supports schema changes wrapped in a transaction.
     *
     * @var bool
     */
    protected $transactions = false;

    /**
     * Get the SQL for the column data type.
     *
     * @param  \Neutrino\Support\Fluent $column
     *
     * @return array
     */
    public function getType(Fluent $column)
    {
        return $this->{'type' . ucfirst($column->get('type'))}($column);
    }

    /**
     * Get the primary key command if it exists on the blueprint.
     *
     * @param  \Neutrino\Database\Schema\Blueprint $blueprint
     * @param  string                              $name
     *
     * @return \Neutrino\Support\Fluent|null
     */
    protected function getCommandByName(Blueprint $blueprint, $name)
    {
        $commands = $this->getCommandsByName($blueprint, $name);

        if (count($commands) > 0) {
            return reset($commands);
        }

        return null;
    }

    /**
     * Get all of the commands with a given name.
     *
     * @param  \Neutrino\Database\Schema\Blueprint $blueprint
     * @param  string                              $name
     *
     * @return array
     */
    protected function getCommandsByName(Blueprint $blueprint, $name)
    {
        return array_filter($blueprint->getCommands(), function ($value) use ($name) {
            return $value->name == $name;
        });
    }

    /**
     * Add a prefix to an array of values.
     *
     * @param  string $prefix
     * @param  array  $values
     *
     * @return array
     */
    public function prefixArray($prefix, array $values)
    {
        return array_map(function ($value) use ($prefix) {
            return $prefix . ' ' . $value;
        }, $values);
    }

    /**
     * Wrap a table in keyword identifiers.
     *
     * @param  mixed $table
     *
     * @return string
     */
    public function wrapTable($table)
    {
        return parent::wrapTable(
            $table instanceof Blueprint ? $table->getTable() : $table
        );
    }

    /**
     * Wrap a value in keyword identifiers.
     *
     * @param  string $value
     * @param  bool   $prefixAlias
     *
     * @return string
     */
    public function wrap($value, $prefixAlias = false)
    {
        return parent::wrap(
            $value instanceof Fluent ? $value->get('name') : $value, $prefixAlias
        );
    }

    /**
     * Format a value so that it can be used in "default" clauses.
     *
     * @param  mixed $value
     *
     * @return string
     */
    protected function getDefaultValue($value)
    {
        return is_bool($value)
            ? "'" . (int)$value . "'"
            : "'" . strval($value) . "'";
    }

    /**
     * Check if this Grammar supports schema changes wrapped in a transaction.
     *
     * @return bool
     */
    public function supportsSchemaTransactions()
    {
        return $this->transactions;
    }
}
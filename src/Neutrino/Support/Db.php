<?php

declare(strict_types=1);

namespace Neutrino\Support;

use Closure;
use Neutrino\Constants\Events;
use Neutrino\Constants\Services;
use Phalcon\Db\Adapter\AbstractAdapter;
use Phalcon\Db\Adapter\AdapterInterface;
use Phalcon\Di\Di;
use Phalcon\Events\Event;
use Phalcon\Events\Manager;
use RuntimeException;

/**
 * Database helpers: a connection by name, and the SQL run (or only pretended) by a callback.
 */
final class Db
{
    /**
     * The `db.<name>` connection, or the default one (`db`).
     */
    public static function connection(?string $name = null): AdapterInterface
    {
        $di = Di::getDefault() ?? throw new RuntimeException('Db: no container.');
        $connection = $di->getShared($name === null ? Services::DB : Services::DB . '.' . $name);

        if (!$connection instanceof AdapterInterface) {
            throw new RuntimeException('Db: "' . ($name ?? Services::DB) . '" is not a database connection.');
        }

        return $connection;
    }

    /**
     * Reads of the schema, run by {@see Db::pretend()} with `$runReads`: `SHOW`, `DESCRIBE`, `PRAGMA` without
     * value, `SELECT` from the catalog (`information_schema`, `pg_catalog`, `pg_class`…, `sqlite_master`). Any other
     * `SELECT` can change the database (`setval()`, locks) and is only listed.
     */
    private const string SCHEMA_READ = '/^\s*(SHOW|DESCRIBE|DESC)\s|^\s*PRAGMA\s+[^=;]+$|^\s*SELECT\s(?:(?!;).)*\sFROM\s+[`"]?(information_schema|pg_catalog|pg_class|pg_namespace|pg_index|pg_attribute|sqlite_master|sqlite_schema)\b/is';

    /**
     * The SQL statements sent by `$callback` on the connection.
     *
     * @param bool $pretend  Cancel the statements instead of running them
     * @param bool $runReads With `$pretend`: run the reads of the schema (`SHOW`, `PRAGMA table_info`, `SELECT`
     *                       from the catalog…) and leave them out of the list
     *
     * @return list<string>
     */
    public static function getQueries(Closure $callback, bool $pretend = false, ?string $connection = null, bool $runReads = false): array
    {
        $db = self::connection($connection);

        if (!$db instanceof AbstractAdapter) {
            throw new RuntimeException('Db: the connection does not support events.');
        }

        $previous = $db->getEventsManager();
        $manager = $previous ?? new Manager();
        $queries = [];

        $listener = static function (Event $event, AbstractAdapter $db) use (&$queries, $pretend, $runReads): bool {
            // Phalcon 5 sets the "real" statement after this event: the current one is getSQLStatement().
            $sql = $db->getSQLStatement();

            if ($pretend && $runReads && preg_match(self::SCHEMA_READ, $sql) === 1) {
                return true;
            }

            $queries[] = $sql;

            if ($pretend && $event->isCancelable()) {
                $event->stop();
            }

            return !$pretend;
        };

        $manager->attach(Events\Db::BEFORE_QUERY, $listener);
        if ($previous === null) {
            $db->setEventsManager($manager);
        }

        try {
            $callback();
        } finally {
            $manager->detach(Events\Db::BEFORE_QUERY, $listener);

            if ($previous === null) {
                // Restores a connection without events manager (setEventsManager() does not accept null).
                (new \ReflectionProperty(AbstractAdapter::class, 'eventsManager'))->setValue($db, null);
            }
        }

        return $queries;
    }

    /**
     * The SQL statements `$callback` would send, without running them.
     *
     * @param bool $runReads Run the reads of the schema (`SHOW`, `PRAGMA table_info`, `SELECT` from the
     *                       catalog…) and leave them out of the list: what a callback writes can depend on them
     *
     * @return list<string>
     */
    public static function pretend(Closure $callback, ?string $connection = null, bool $runReads = false): array
    {
        return self::getQueries($callback, true, $connection, $runReads);
    }
}

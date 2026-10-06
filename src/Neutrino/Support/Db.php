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
     * The SQL statements sent by `$callback` on the connection.
     *
     * @param bool $pretend Cancel the statements instead of running them
     *
     * @return list<string>
     */
    public static function getQueries(Closure $callback, bool $pretend = false, ?string $connection = null): array
    {
        $db = self::connection($connection);

        if (!$db instanceof AbstractAdapter) {
            throw new RuntimeException('Db: the connection does not support events.');
        }

        $previous = $db->getEventsManager();
        $manager = $previous ?? new Manager();
        $queries = [];

        $listener = static function (Event $event, AbstractAdapter $db) use (&$queries, $pretend): bool {
            // Phalcon 5 sets the "real" statement after this event: the current one is getSQLStatement().
            $queries[] = $db->getSQLStatement();

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
     * @return list<string>
     */
    public static function pretend(Closure $callback, ?string $connection = null): array
    {
        return self::getQueries($callback, true, $connection);
    }
}

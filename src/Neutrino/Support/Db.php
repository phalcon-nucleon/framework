<?php

namespace Neutrino\Support;

use Neutrino\Constants\Events;
use Neutrino\Constants\Services;
use Phalcon\Db\Adapter;
use Phalcon\Di;
use Phalcon\Events\Event;
use Phalcon\Events\Manager;

/**
 * Class Db
 *
 * @package Neutrino\Support
 */
class Db
{
    /**
     * Return queries randed behind callback.
     *
     * @param \Closure $callback
     * @param bool     $pretend
     *
     * @return array
     */
    public static function getQueries(\Closure $callback, $pretend = false)
    {
        $db = Di::getDefault()->get(Services::DB);

        if (is_null($em = $db->getEventsManager())) {
            $db->setEventsManager($em = new Manager());
        }

        $queries = [];

        $listener = function (Event $event, Adapter $db) use (&$queries, $pretend) {
            $queries[] = $db->getRealSQLStatement();

            if ($pretend && $event->isCancelable()) {
                $event->stop();
            }

            return !$pretend;
        };

        $em->attach(Events\Db::BEFORE_QUERY, $listener);

        $callback();

        $em->detach(Events\Db::BEFORE_QUERY, $listener);

        return $queries;
    }

    /**
     * Pretend run SQL behind callback and return queries launched.
     *
     * @param \Closure $callback
     *
     * @return array
     */
    public static function pretend(\Closure $callback)
    {
        return self::getQueries($callback, true);
    }
}

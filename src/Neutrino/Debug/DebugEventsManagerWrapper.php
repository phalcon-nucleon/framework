<?php

namespace Neutrino\Debug;

use Neutrino\Error\Helper;
use Phalcon\Events\Manager;
use Phalcon\Events\ManagerInterface;

/**
 * Class DebugEventsManagerWrapper
 *
 * @package App\Debug
 */
class DebugEventsManagerWrapper extends Manager implements ManagerInterface
{

    protected static $events;

    public static function getEvents()
    {
        return self::$events;
    }

    protected $manager;

    private function __verboseType($var){
        switch ($type = gettype($var)) {
            case 'array':
                return Helper::verboseType($var);
            case 'object':
                $class = explode('\\', get_class($var));
                return 'object(' . array_pop($class) . ')';
            case 'NULL':
                return 'null';
            case 'unknown type':
                return '?';
            case 'resource':
            case 'resource (closed)':
                return $type;
            case 'string':
                if (strlen($var) > 40) {
                    return "'" . substr($var, 0, 30) . '...\'[' . strlen($var) . ']';
                }
            case 'boolean':
            case 'integer':
            case 'double':
            default:
                return var_export($var, true);
        }
    }

    public function fire($eventType, $source, $data = null, $cancelable = true, ...$args)
    {
        $eventParts = explode(':', $eventType, 2);
        self::$events[] = [
          'space' => $eventParts[0],
          'type' => $eventParts[1],
          'src' => $this->__verboseType($source),
          'data' => !is_null($data) ? $this->__verboseType($data) : null,
          'raw_data' => $data,
          'mt' => microtime(true),
        ];

        return $this->manager->fire($eventType, $source, $data, $cancelable, ...$args);
    }

    public function __construct(ManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function attach($eventType, $handler, $priority = 100, ...$args)
    {
        return $this->manager->attach($eventType, $handler, $priority, ...$args);
    }

    public function detach($eventType, $handler, ...$args)
    {
        return $this->manager->detach($eventType, $handler, ...$args);
    }

    public function detachAll($type = null, ...$args)
    {
        return $this->manager->detachAll($type, ...$args);
    }

    public function getListeners($type, ...$args)
    {
        return $this->manager->getListeners($type, ...$args);
    }

    public function enablePriorities($enablePriorities, ...$args)
    {
        return $this->manager->enablePriorities($enablePriorities, ...$args);
    }

    public function arePrioritiesEnabled(...$args)
    {
        return $this->manager->arePrioritiesEnabled(...$args);
    }

    public function collectResponses($collect, ...$args)
    {
        return $this->manager->collectResponses($collect, ...$args);
    }

    public function isCollecting(...$args)
    {
        return $this->manager->isCollecting(...$args);
    }

    public function getResponses(...$args)
    {
        return $this->manager->getResponses(...$args);
    }

    public function hasListeners($type, ...$args)
    {
        return $this->manager->hasListeners($type, ...$args);
    }

    public function __call($name, $arguments)
    {
        return $this->manager->$name(...$arguments);
    }
}

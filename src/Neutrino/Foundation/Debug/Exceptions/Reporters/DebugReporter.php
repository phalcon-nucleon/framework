<?php


namespace Neutrino\Foundation\Debug\Exceptions\Reporters;

use Neutrino\Foundation\Debug\Exceptions\ReporterInterface;

/**
 * Class DebugReporter
 * @package Neutrino\Foundation\Debug\Exceptions
 */
class DebugReporter implements ReporterInterface
{
    /**
     * @var \Throwable[]
     */
    private static $errors = [];

    /**
     * @return \Throwable[]
     */
    public static function errors()
    {
        return self::$errors;
    }

    /**
     * @param \Throwable|\Exception $throwable
     * @param \Phalcon\DiInterface  $container
     */
    public function report($throwable, $container = null)
    {
        self::$errors[] = $throwable;
    }
}
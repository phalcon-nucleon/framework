<?php

namespace Luxury\Support\DesignPatterns;

/**
 * Class Singleton
 *
 * Singleton Design Pattern
 * 
 * @package  Luxury\Support\DesignPatterns
 */
abstract class Singleton
{
    /**
     * @var static
     */
    private static $instance;

    /**
     * Singleton constructor.
     */
    protected function __construct()
    {
    }

    /**
     * @throws \RuntimeException
     */
    final private function __clone()
    {
        throw new \RuntimeException('Try to clone Singleton instance.');
    }

    /**
     * Instantiate & return static instance
     *
     * @return static
     */
    public static function instance()
    {
        if (self::$instance == null) {
            self::$instance = new static();
        }

        return self::$instance;
    }
}

<?php

namespace Neutrino\Dotconst\Extensions;

/**
 * Class Extension
 *
 * @package Neutrino\Dotconst\Extensions
 */
abstract class Extension
{
    protected $identifier;

    /**
     * Extension constructor.
     */
    public function __construct()
    {
        if (empty($this->identifier)) {
            throw new \LogicException(__CLASS__ . '::$identifier can\'t be empty');
        }
    }

    /**
     * @param $value
     *
     * @return bool
     */
    final public function identify($value)
    {
        return preg_match("#^@{$this->identifier}@?#", $value) === 1;
    }

    protected function match($value)
    {
        preg_match("#^@{$this->identifier}@?#", $value, $match);

        return $match;
    }

    /**
     * @param string $value
     * @param string $path
     *
     * @return string
     */
    abstract public function parse($value, $path);

    /**
     * @param string $value
     * @param string $path
     *
     * @return string
     */
    abstract public function compile($value, $path);
}

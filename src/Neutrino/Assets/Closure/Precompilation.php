<?php

namespace Neutrino\Assets\Closure;

/**
 * Class Speedhack
 *
 * @package Neutrino\Assets\Closure
 */
abstract class Precompilation
{

    protected $options = [];

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    abstract public function precompile($content);
}

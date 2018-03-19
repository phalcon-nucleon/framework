<?php

namespace Neutrino\View\Engines;

/**
 * Class EngineRegister
 *
 * @package Neutrino\View\Engines
 */
abstract class EngineRegister
{
    /**
     * Create a closure
     *
     * @return \Closure
     */
    final static public function getRegisterClosure()
    {
        return function ($view, $di) {
            return (new static)->register($view, $di);
        };
    }

    /**
     * @param $view
     * @param $di
     *
     * @return \Phalcon\Mvc\View\Engine
     */
    abstract public function register($view, $di);
}

<?php

namespace Neutrino\Foundation\Debug\Exceptions;

/**
 * Interface RenderInterface
 *
 * @package Neutrino\Foundation\Debug\Exceptions
 */
interface RenderInterface
{
    /**
     * @param \Throwable|\Exception $throwable
     * @param \Phalcon\DiInterface  $container
     */
    public function render($throwable, $container = null);
}
<?php

namespace Neutrino\Foundation\Debug\Exceptions;

/**
 * Interface ReporterInterface
 *
 * @package Neutrino\Foundation\Debug\Exceptions
 */
interface ReporterInterface
{
    /**
     * @param \Throwable|\Exception $throwable
     * @param \Phalcon\DiInterface  $container
     */
    public function report($throwable, $container = null);
}
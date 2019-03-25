<?php

namespace Neutrino\Debug\Exceptions;

/**
 * Interface ExceptionHandlerInterface
 *
 * @package Neutrino\Debug\Exceptions
 */
interface ExceptionHandlerInterface
{
    /**
     * @param \Exception|\Throwable $throwable
     *
     * @return void
     */
    public function handle($throwable);

    /**
     * @param \Exception|\Throwable $throwable
     *
     * @return void
     */
    public function report($throwable);
}
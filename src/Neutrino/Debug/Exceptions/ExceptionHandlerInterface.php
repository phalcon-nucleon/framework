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

    /**
     * @param \Exception|\Throwable $throwable
     * @param \Phalcon\Http\RequestInterface $request
     *
     * @return \Phalcon\Http\ResponseInterface
     */
    public function render($throwable, $request = null);

    /**
     * @param \Exception|\Throwable $throwable
     *
     * @return void
     */
    public function renderConsole($throwable);
}
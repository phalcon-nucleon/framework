<?php


namespace Neutrino\Foundation\Debug\Exceptions\Renders;

use Neutrino\Foundation\Debug\Exceptions\RenderInterface;

/**
 * Class WebRender
 * @package Neutrino\Foundation\Debug\Exceptions\Renders
 */
class WebRender implements RenderInterface
{
    /**
     * @param \Throwable|\Exception $throwable
     * @param \Phalcon\DiInterface  $container
     *
     * @return \Phalcon\Http\ResponseInterface
     */
    public function render($throwable, $container = null)
    {
        // TODO: Implement render() method.
    }
}
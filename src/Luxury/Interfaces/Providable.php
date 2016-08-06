<?php

namespace Luxury\Interfaces;

use Phalcon\DiInterface;

/**
 * Class Providable
 *
 * @package Luxury\Providers
 */
interface Providable
{
    /**
     * @param \Phalcon\DiInterface $di
     *
     * @return void
     */
    public function register(DiInterface $di);
}

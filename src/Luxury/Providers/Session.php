<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\Exceptions\SessionAdapterNotFound;
use Phalcon\DiInterface;

/**
 * Class Session
 *
 * @package Luxury\Foundation\Bootstrap
 */
class Session extends Provider
{
    protected $name = Services::SESSION;

    protected $shared = true;

    /**
     * Start the session the first time some component request the session service
     *
     * @param \Phalcon\DiInterface $di
     *
     * @throws \Luxury\Exceptions\SessionAdapterNotFound
     *
     * @return mixed|\Phalcon\Session\Adapter|\Phalcon\Session\AdapterInterface
     */
    protected function register(DiInterface $di)
    {
        $di->set(Services::SESSION_BAG, \Phalcon\Session\Bag::class);

        /* @var \Phalcon\Session\Adapter|\Phalcon\Session\AdapterInterface $session */
        $class = 'Phalcon\Session\Adapter\\' . $di->getShared(Services::CONFIG)->session->adapter;
        try {
            $session = new $class();
        } catch (\Exception $e) {
            throw new SessionAdapterNotFound($e);
        }

        $session->start();

        return $session;
    }
}

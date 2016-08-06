<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\Exceptions\SessionAdapterNotFound;
use Luxury\Interfaces\Providable;
use Phalcon\DiInterface;

/**
 * Class Session
 *
 * @package Luxury\Foundation\Bootstrap
 */
class Session implements Providable
{

    /**
     * Start the session the first time some component request the session service
     *
     * @param \Phalcon\DiInterface $di
     */
    public function register(DiInterface $di)
    {
        $di->setShared(Services::SESSION, function () {
            /* @var \Phalcon\Di $this */
            /* @var \Phalcon\Session\Adapter|\Phalcon\Session\AdapterInterface $session */
            $class =
                'Phalcon\Session\Adapter\\' . $this->getShared(Services::CONFIG)->session->adapter;
            try {
                $session = new $class();
            } catch (\Exception $e) {
                throw new SessionAdapterNotFound($e);
            }

            $session->start();

            return $session;
        });

        $di->set(Services::SESSION_BAG, \Phalcon\Session\Bag::class);
    }
}

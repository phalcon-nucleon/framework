<?php

namespace Luxury\Providers;

use Luxury\Constants\Services;
use Luxury\Exceptions\SessionAdapterNotFound;
use Phalcon\Session\Bag;

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
     * @throws \Luxury\Exceptions\SessionAdapterNotFound
     *
     * @return mixed|\Phalcon\Session\Adapter|\Phalcon\Session\AdapterInterface
     */
    public function registering()
    {
        $di = $this->getDI();

        $di->set(Services::SESSION_BAG, Bag::class);
        $di->setShared($this->name, function () {
            /** @var \Phalcon\DiInterface $this */
            /** @var \Phalcon\Session\Adapter|\Phalcon\Session\AdapterInterface $session */
            $class =
                'Phalcon\Session\Adapter\\' . $this->getShared(Services::CONFIG)->session->adapter;
            try {
                $session = new $class();
            } catch (\Error $e) {
                throw new SessionAdapterNotFound($e);
            }

            $session->start();

            return $session;
        });
    }

    /**
     * @return mixed
     */
    protected function register()
    {
        return;
    }
}

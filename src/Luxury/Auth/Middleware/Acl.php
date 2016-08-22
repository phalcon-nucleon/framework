<?php

namespace Luxury\Auth\Middleware;

use Luxury\Auth\AuthManager;
use Luxury\Auth\Authorizable;
use Luxury\Constants\Services;
use Luxury\Foundation\Middleware\Controller as ControllerMiddleware;
use Luxury\Middleware\BeforeMiddleware;
use Phalcon\Acl\Resource as AclResource;
use Phalcon\Events\Event;

/**
 * Class Acl
 *
 * @package     Luxury\Auth\Middleware
 */
class Acl extends ControllerMiddleware implements BeforeMiddleware
{
    /**
     * Acl Adapter.
     *
     * @var \Phalcon\Acl\Adapter
     */
    private $acl;

    /**
     * Controller checked by the middleware.
     *
     * @var string
     */
    protected $controller;

    /**
     * Define the resources of the controller that are considered "private".
     *
     * @var array
     */
    protected $resources = [];

    /**
     * Create the AclMiddleware.
     *
     * @param string $controller
     * @param array  $resources
     *
     * @return \Luxury\Auth\Middleware\Acl
     */
    public static function create(string $controller, array $resources) : Acl
    {
        $acl = new static;

        $acl->controller = $controller;
        $acl->resources  = $resources;

        return $acl;
    }

    /**
     * Called before the execution of handler
     *
     * @param \Phalcon\Events\Event     $event
     * @param \Phalcon\Dispatcher|mixed $source
     * @param mixed|null                $data
     *
     * @throws \Exception
     * @return bool
     */
    public function before(Event $event, $source, $data = null)
    {
        $this->init();

        $di = $this->getDI();

        /** @var \Phalcon\Mvc\Dispatcher $dispatcher */
        $dispatcher = $di->getShared(Services::DISPATCHER);
        /** @var AuthManager $auth */
        $auth = $di->getShared(Services::AUTH);

        if (isset($this->resources[$action = $dispatcher->getActionName()])) {
            if (is_null($user = $auth->user()) || !($user instanceof Authorizable)) {
                return false;
            }

            $controller = $dispatcher->getControllerName();
            if (!$this->acl->isAllowed($user->getRole(), $controller, $action)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Initialize the Acl Component
     */
    private function init()
    {
        if (!empty($this->acl)) {
            return;
        }

        /** @var \Phalcon\Cache\BackendInterface $cache */
        $cache = $this->getDI()->getShared(Services::CACHE);

        $acl = $cache->get(Services::ACL);

        if (empty($acl)) {
            /** @var \Phalcon\Acl\Adapter $acl */
            $acl = $this->getDI()->getShared(Services::ACL);
        }

        $this->acl = $acl;

        $resourceNotLoaded = true;
        foreach ($this->acl->getResources() as $resource) {
            if ($resource->getName() == $this->controller) {
                $resourceNotLoaded = false;
                break;
            }
        }

        if ($resourceNotLoaded) {
            $this->acl->addResource(new AclResource($this->controller), $this->resources);

            $cache->save(Services::ACL, $acl, 1);
        }
    }
}

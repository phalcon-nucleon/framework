<?php

namespace Luxury\Observator;

use Luxury\Support\Facades\Log;
use Phalcon\Application;
use Phalcon\Di;
use Phalcon\Events\Event;

/**
 * Class Events
 *
 * @package     Luxury\Observator
 */
class Events
{
    private static $events = [
        'dispatch:beforeDispatchLoop',
        'dispatch:beforeDispatch',
        'dispatch:beforeNotFoundAction',
        'dispatch:beforeExecuteRoute',
        'dispatch:afterInitialize',
        'dispatch:afterExecuteRoute',
        'dispatch:afterDispatch',
        'dispatch:afterDispatchLoop',
        'dispatch:beforeException', // + CLI


        'loader:beforeCheckClass',
        'loader:pathFound',
        'loader:beforeCheckPath',
        'loader:afterCheckClass',

        'acl:beforeCheckAccess',
        'acl:afterCheckAccess',

        'console:beforeStartModule',
        'console:afterStartModule',
        'console:beforeHandleTask',
        'console:afterHandleTask',


        'db:beforeQuery',
        'db:afterQuery',
        'db:beginTransaction',
        'db:createSavepoint',
        'db:rollbackTransaction',
        'db:rollbackSavepoint',
        'db:commitTransaction',
        'db:releaseSavepoint',

        'application:boot',
        'application:beforeStartModule',
        'application:afterStartModule',
        'application:beforeHandleRequest',
        'application:afterHandleRequest',
        'application:viewRender',
        'application:beforeSendResponse',


        'collection:beforeValidation',
        'collection:beforeValidationOnCreate',
        'collection:beforeValidationOnUpdate',
        'collection:validation',
        'collection:onValidationFails',
        'collection:afterValidationOnCreate',
        'collection:afterValidationOnUpdate',
        'collection:afterValidation',
        'collection:beforeSave',
        'collection:beforeUpdate',
        'collection:beforeCreate',
        'collection:afterUpdate',
        'collection:afterCreate',
        'collection:afterSave',
        'collection:notSave',
        'collection:notDeleted',
        'collection:notSaved',

        'micro:beforeHandleRoute',
        'micro:beforeExecuteRoute',
        'micro:afterExecuteRoute',
        'micro:beforeNotFound',
        'micro:afterHandleRoute',

        'model:notDeleted',
        'model:notSaved',
        'model:onValidationFails',
        'model:beforeValidation',
        'model:beforeValidationOnCreate',
        'model:beforeValidationOnUpdate',
        'model:afterValidationOnCreate',
        'model:afterValidationOnUpdate',
        'model:afterValidation',
        'model:beforeSave',
        'model:beforeUpdate',
        'model:beforeCreate',
        'model:afterUpdate',
        'model:afterCreate',
        'model:afterSave',
        'model:notSave',
        'model:beforeDelete',
        'model:afterDelete',

        'view:beforeRenderView',
        'view:afterRenderView',
        'view:notFoundView',
        'view:beforeRender',
        'view:afterRender',

        'collectionManager:afterInitialize',

        'modelsManager:afterInitialize',

        'volt:compileFunction',
        'volt:compileFilter',
        'volt:resolveExpression',
        'volt:compileStatement',
    ];

    /**
     * @var \Phalcon\Events\Event[]
     */
    private static $raised = [];
    /**
     * @var bool[]
     */
    private static $logged = [];

    /**
     * @param Application $app
     * @param string      $space
     * @param string|null $name
     */
    public static function observe(Application $app, $space, $name = null)
    {
        if ($name == null) {
            $name = $space;
        } else {
            $name = $space . $name;
        }

        if (empty($name) || !in_array($name, self::$events)) {
            return;
        }

        Log::info('OEvent:observe:o:' . $name);

        if (!isset(self::$logged[$name])) {
            $em = $app->getEventsManager();

            Log::info('OEvent:observe:' . $name);
            $em->attach($name, function (Event $event) {
                Log::info(
                    'OEvent:observe:raised' . get_class(
                        $event->getSource()
                    ) . ':' . $event->getType()
                );
                Events::$raised[] = $event;
            });

            self::$logged[$name] = true;
        }
    }

    /**
     * @param Application $app
     */
    public static function observeAll(Application $app)
    {
        foreach (self::$events as $event) {
            self::observe($app, $event);
        }
    }

    /**
     * @return \Phalcon\Events\Event[]
     */
    public static function getRaised()
    {
        return self::$raised;
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: xlzi590
 * Date: 18/10/2016
 * Time: 11:27
 */

namespace Luxury\Support\Facades;


use Luxury\Constants\Services;

/**
 * Class Session
 * @package Luxury\Support\Facades
 *
 * @method static start() Starts session, optionally using an adapter
 * @method static setOptions(array $options) Sets session options
 * @method static array getOptions() Get internal options
 * @method static mixed get(string $index, $defaultValue = null) Gets a session variable from an application context
 * @method static set(string $index, $value) Sets a session variable in an application context
 * @method static bool has(string $index) Check whether a session variable is set in an application context
 * @method static remove(string $index) Removes a session variable from an application context
 * @method static string getId() Returns active session id
 * @method static bool isStarted() Check whether the session has been started
 * @method static bool destroy(bool $removeData = false) Destroys the active session
 * @method static \Phalcon\Session\AdapterInterface regenerateId(bool $deleteOldSession = true) Regenerate session's id
 * @method static setName(string $name) Set session name
 * @method static string getName() Get session name
 */
class Session extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Services::SESSION;
    }
}
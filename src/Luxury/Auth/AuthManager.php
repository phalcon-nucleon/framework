<?php

namespace Luxury\Auth;

use Luxury\Auth\Model\User;
use Luxury\Constants\Services;
use Luxury\Support\Arr;
use Phalcon\Di\Injectable as Injector;

/**
 * Class Auth
 *
 * @package Luxury\Auth
 */
class AuthManager extends Injector
{
    /**
     * User Authenticable
     *
     * @var User
     */
    protected $user;

    /**
     * Authorization Gate
     *
     * @var Authorize
     */
    private $adapter;

    /**
     * Indicates if the logout method has been called.
     *
     * @var bool
     */
    protected $loggedOut = false;

    /**
     * Auth constructor.
     *
     * @param Authorize $adapter
     */
    public function __construct(Authorize $adapter = null)
    {
        $this->adapter = $adapter;
    }

    /**
     * Return the user authenticated
     *
     * @return \Luxury\Auth\Model\User|null
     */
    public function user()
    {
        if ($this->loggedOut) {
            return null;
        }

        if (!is_null($this->user)) {
            return $this->user;
        }

        if (is_null($id = $this->retrieveId())) {
            return null;
        }

        $this->user = $this->retrieveUserById($id);

        return $this->user;
    }

    /**
     * If user is NOT logged into the system return true else false;
     *
     * @return bool Guest is true, Loggedin is false
     */
    public function guest()
    {
        return !$this->check();
    }

    /**
     * authenticate user
     *
     * @param  array $credentials
     *
     * @return User|static
     */
    public function attempt(array $credentials = [])
    {
        $user = User::findFirst([
            User::getAuthIdentifierName() => Arr::get($credentials, User::getAuthIdentifierName()),
            User::getAuthPasswordName()   => Arr::get($credentials, User::getAuthPasswordName()),
        ]);

        if (!is_null($user)) {
            $this->login($user);

            return $user;
        }

        return null;
    }

    /**
     * Determine if user is authenticated
     *
     * @return bool
     */
    public function check()
    {
        return !is_null($this->user());
    }

    /**
     * Log out of the application
     */
    public function logout()
    {
        $this->user      = null;
        $this->loggedOut = true;

        $this->getDI()->getShared(Services::SESSION)->destroy();
    }

    /**
     * Get currently logged user's id
     *
     * @return mixed|null
     */
    public function retrieveId()
    {
        return $this->getDI()->getShared(Services::SESSION)->get($this->sessionKey());
    }

    /**
     * Log a user into the application
     *
     * @param $user
     *
     * @return bool
     */
    public function login($user)
    {
        if (!$user) {
            return false;
        }

        $this->regenerateSessionId();

        $this->getDI()->getShared(Services::SESSION)->set($this->sessionKey(), $user->id);

        return true;
    }

    /**
     * Log a user into the application using id
     *
     * @param int $id
     *
     * @return User
     */
    public function loginUsingId($id)
    {
        $this->login($user = $this->retrieveUserById($id));

        return $user;
    }

    /**
     * @param int $id
     *
     * @return User
     */
    protected function retrieveUserById($id)
    {
        return User::find(['id' => $id]);
    }

    /**
     * Regenerate Session ID
     */
    protected function regenerateSessionId()
    {
        $this->getDI()->getShared(Services::SESSION)->regenerateId();
    }

    /**
     * Retrieve session id
     *
     * @return mixed
     */
    private function sessionKey()
    {
        return $this->getDI()->getShared(Services::CONFIG)->session->id;
    }
}

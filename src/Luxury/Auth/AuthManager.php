<?php

namespace Luxury\Auth;

use Luxury\Constants\Services;
use Luxury\Interfaces\Auth\Authenticable as AuthenticableInterface;
use Luxury\Support\Arr;
use Luxury\Support\Facades\Session;
use Phalcon\Di\Injectable as Injector;

/**
 * Class Auth
 *
 * @package Luxury\Auth
 */
class AuthManager extends Injector
{
    /**
     * User AuthenticableInterface
     *
     * @var \Luxury\Foundation\Auth\User
     */
    protected $user;

    /**
     * Indicates if the logout method has been called.
     *
     * @var bool
     */
    protected $loggedOut = false;

    /**
     * @var string
     */
    protected $model;

    /**
     * Return the user authenticated
     *
     * @return \Luxury\Foundation\Auth\User|null
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
     * @return \Luxury\Foundation\Auth\User
     */
    public function attempt(array $credentials = [])
    {
        $class = $this->modelClass();

        $identifier = $class::getAuthIdentifierName();
        $password   = $class::getAuthPasswordName();

        $user = $class::findFirst([
            'conditions' => "$identifier = :identifier: AND $password = :password:",
            'bind'       => [
                'identifier' => Arr::fetch($credentials, $identifier),
                'password'   => $this->security->hash(Arr::fetch($credentials, $password)),
            ],
        ]);

        if (!empty($user)) {
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

        Session::destroy();
    }

    /**
     * Get currently logged user's id
     *
     * @return mixed|null
     */
    public function retrieveId()
    {
        return Session::get($this->sessionKey());
    }

    /**
     * Log a user into the application
     *
     * @param AuthenticableInterface $user
     *
     * @return bool
     */
    public function login(AuthenticableInterface $user)
    {
        if (!$user) {
            return false;
        }

        $this->regenerateSessionId();

        Session::set($this->sessionKey(), $user->id);

        $this->user = $user;

        return true;
    }

    /**
     * Log a user into the application using id
     *
     * @param int $id
     *
     * @return AuthenticableInterface|\Phalcon\Mvc\Model
     */
    public function loginUsingId($id)
    {
        $this->login($user = $this->retrieveUserById($id));

        return $user;
    }

    /**
     * @param int $id
     *
     * @return AuthenticableInterface|\Phalcon\Mvc\Model
     */
    protected function retrieveUserById($id)
    {
        $class = $this->modelClass();

        return $class::findFirst($id);
    }

    /**
     * Regenerate Session ID
     */
    protected function regenerateSessionId()
    {
        Session::regenerateId();
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

    /**
     * @return string|\Luxury\Foundation\Auth\User
     */
    private function modelClass()
    {
        if (!isset($this->model)) {
            $this->model = '\\' . $this->getDI()->getShared(Services::CONFIG)->auth->model;
        }

        return $this->model;
    }
}

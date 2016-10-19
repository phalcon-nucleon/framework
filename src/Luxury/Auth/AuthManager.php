<?php

namespace Luxury\Auth;

use Luxury\Constants\Services;
use Luxury\Foundation\Auth\User;
use Luxury\Interfaces\Auth\Authenticable as AuthenticableInterface;
use Luxury\Support\Arr;
use Luxury\Support\Facades\Session;
use Luxury\Support\Str;
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

        $user = null;

        if (!is_null($id = $this->retrieveIdentifier())) {
            $user = $this->retrieveUserByIdentifier($id);
        }

        $cookies = $this->getDI()->getShared(Services::COOKIES);
        if (empty($user) && $cookies->has('remember_me')) {
            $recaller = $cookies->get('remember_me');
            list($identifier, $token) = explode('|', $recaller);

            if ($identifier && $token) {
                $user = $this->retrieveUserByToken($identifier, $token);
            }
        }

        $this->user = $user;

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
     * @param array $credentials
     * @param bool  $remember
     *
     * @return \Luxury\Foundation\Auth\User
     */
    public function attempt(array $credentials = [], bool $remember = false)
    {
        $user = $this->retrieveUserByCredentials($credentials);

        if (!empty($user)) {
            $this->login($user, $remember);

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
    public function retrieveIdentifier()
    {
        return Session::get($this->sessionKey());
    }

    /**
     * Log a user into the application
     *
     * @param User $user
     * @param bool $remember
     *
     * @return bool
     */
    public function login(User $user, bool $remember = false)
    {
        if (!$user) {
            return false;
        }

        $this->regenerateSessionId();

        Session::set($this->sessionKey(), $user->getAuthIdentifier());

        if ($remember) {
            $rememberToken = Str::random(60);

            /** @var \Phalcon\Http\Response\Cookies|\Phalcon\Http\Response\CookiesInterface $cookies */
            $cookies = $this->getDI()->getShared(Services::COOKIES);
            $cookies->set('remember_me', $user->getAuthIdentifier() . '|' . $rememberToken);

            $user->setRememberToken($rememberToken);

            $user->save();
        }

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
     * @return User
     */
    protected function retrieveUserById($id)
    {
        $class = $this->modelClass();

        return $class::findFirst($id);
    }

    /**
     * @param int $id
     *
     * @return User
     */
    protected function retrieveUserByIdentifier($id)
    {
        $class = $this->modelClass();

        $result = $class::query()
            ->andWhere($class::getAuthIdentifierName() . ' = :auth_identifier:',
                ['auth_identifier' => $id])
            ->limit(1)
            ->execute();

        return $result->getFirst();
    }

    /**
     * @param int    $id
     * @param string $token
     *
     * @return AuthenticableInterface|\Phalcon\Mvc\Model
     */
    protected function retrieveUserByToken($id, $token)
    {
        $class = $this->modelClass();

        $result = $class::query()
            ->andWhere($class::getAuthIdentifierName() . ' = :auth_identifier:',
                ['auth_identifier' => $id])
            ->andWhere($class::getRememberTokenName() . ' = :token_identifier:',
                ['token_identifier' => $token])
            ->limit(1)
            ->execute();

        return $result->getFirst();
    }

    /**
     * @param array $credentials
     *
     * @return \Luxury\Foundation\Auth\User
     */
    protected function retrieveUserByCredentials(array $credentials)
    {
        $class = $this->modelClass();

        $identifier = $class::getAuthIdentifierName();
        $password   = $class::getAuthPasswordName();

        $result = $class::query()
            ->andWhere($identifier . ' = :identifier:', ['identifier' => Arr::fetch($credentials, $identifier)])
            ->limit(1)
            ->execute();

        /** @var \Luxury\Foundation\Auth\User $user */
        $user = $result->getFirst();

        if ($user && $this->security->checkHash(Arr::fetch($credentials, $password), $user->getAuthPassword())
        ) {
            return $user;
        }

        return null;
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

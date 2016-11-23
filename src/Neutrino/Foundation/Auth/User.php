<?php

namespace Neutrino\Foundation\Auth;

use Neutrino\Auth\Authenticable;
use Neutrino\Interfaces\Auth\Authenticable as AuthenticableInterface;
use Neutrino\Model;

/**
 * Class User
 *
 *  @package Neutrino\Auth\Model
 */
class User extends Model implements AuthenticableInterface
{
    use Authenticable;
/*
    public function beforeSave(){
        $arg = func_get_args();
    }
    public function prepareSave(){
        $arg = func_get_args();
    }

    public function columnMap()
    {
        //Keys are the real names in the table and
        //the values their names in the application
        return array(
            'id' => 'id',
            'name' => 'name',
            'email' => 'email',
            'password' => 'password',
            'remember_token' => 'remember_token'
        );
    }
    public $name;
    public $email;
    public $password;
    public $remember_token;*/
}

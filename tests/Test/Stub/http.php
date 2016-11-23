<?php

use Luxury\Support\Facades\Router;

Router::addGet('/get', [
    'controller' => 'Stub',
    'action'     => 'index'
]);

Router::addPost('/post', [
    'controller' => 'Stub',
    'action'     => 'index',
    'namespace'  => 'Test\Stub'
]);

Router::addGet('/u/:int', [
    'controller' => 'Stub',
    'action'     => 'index',
    'namespace'  => 'Test\Stub',
    'user' => 1
]);

Router::add('/get-head', [
    'controller' => 'Stub',
    'action'     => 'index',
    'namespace'  => 'Test\Stub',
    'middleware' => \Luxury\Http\Middleware\Csrf::class
], ['GET', 'HEAD']);
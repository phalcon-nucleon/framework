<?php

use Neutrino\Support\Facades\Router;

Router::addGet('/get', [
    'controller' => 'Stub',
    'action'     => 'index'
]);

Router::addPost('/post', [
    'controller' => 'Stub',
    'action'     => 'index',
    'namespace'  => \Fake\Kernels\Http\Controllers::class
]);

Router::addGet('/u/:int', [
    'controller' => 'Stub',
    'action'     => 'index',
    'namespace'  => \Fake\Kernels\Http\Controllers::class,
    'user' => 1
]);

Router::add('/get-head', [
    'controller' => 'Stub',
    'action'     => 'index',
    'namespace'  => \Fake\Kernels\Http\Controllers::class,
    'middleware' => \Neutrino\Http\Middleware\Csrf::class
], ['GET', 'HEAD']);


Router::addGet('/back/:controller/:action', [
    'namespace'  => \Fake\Kernels\Http\Controllers::class
]);

<?php

use Neutrino\Support\Facades\Router;

Router::addGet('/hello', [
    'namespace'  => 'Bench\Controllers',
    'controller' => 'index',
    'action'     => 'hello',
]);

Router::addGet('/hello-mw1', [
    'namespace'  => 'Bench\Controllers',
    'controller' => 'index',
    'action'     => 'hello',
    'middleware' => [\Bench\Middlewares\Noop::class],
]);

Router::addGet('/hello-mw3', [
    'namespace'  => 'Bench\Controllers',
    'controller' => 'index',
    'action'     => 'hello',
    'middleware' => [\Bench\Middlewares\Noop::class, \Bench\Middlewares\Noop::class, \Bench\Middlewares\Noop::class],
]);

Router::addGet('/hello-throttle', [
    'namespace'  => 'Bench\Controllers',
    'controller' => 'index',
    'action'     => 'hello',
    'middleware' => [\Neutrino\Http\Middleware\ThrottleRequest::class => [1000, 60]],
]);

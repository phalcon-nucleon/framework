<?php

use Neutrino\Support\Facades\Router;

Router::addGet('/hello', [
    'namespace'  => 'Bench\Controllers',
    'controller' => 'index',
    'action'     => 'hello',
]);

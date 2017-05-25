<?php

global $config;

$config = [];

/*
|--------------------------------------------------------------------------
| Register The Composer Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader
| for our application. We just need to utilize it! We'll require it
| into the script here so that we do not have to worry about the
| loading of any our classes "manually". Feels great to relax.
|
*/
require __DIR__ . '/../vendor/autoload.php';

\Neutrino\Dotconst\Loader::load(__DIR__.'/.fake/nucleon.app');

echo $version = Neutrino\Version::get() . PHP_EOL;
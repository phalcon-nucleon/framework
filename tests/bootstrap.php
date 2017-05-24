<?php

use Phalcon\Loader;

$loader = new Loader;

$loader->registerDirs([__DIR__], true);

$loader->register();

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

echo $version = Neutrino\Version::get() . PHP_EOL;


define('APP_ENV', 'test');
define('BASE_PATH', __DIR__ . '/.fake/nucleon_app');
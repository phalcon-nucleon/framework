<?php

return [
    'views_dir'     => BASE_PATH . '/resources/views/',
    'compiled_path' => BASE_PATH . '/storage/views/',
    'engines'       => ['.volt' => \Neutrino\View\Engines\Volt\VoltEngineRegister::class],
];

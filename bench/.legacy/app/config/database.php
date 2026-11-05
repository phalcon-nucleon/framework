<?php

return [
    'default'     => 'main',
    'connections' => [
        'main' => ['adapter' => \Phalcon\Db\Adapter\Pdo\Sqlite::class, 'config' => ['dbname' => ':memory:']],
    ],
];

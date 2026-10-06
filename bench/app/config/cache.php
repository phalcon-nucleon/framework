<?php

return [
    'default' => 'memory',
    'stores'  => [
        'memory' => ['adapter' => 'memory'],
        'redis'  => ['adapter' => 'redis', 'options' => ['host' => getenv('REDIS_HOST') ?: '127.0.0.1']],
    ],
];

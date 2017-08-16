<?php

return [
    'database' => [
        'host' => 'localhost',
        'port' => 27017,
        'name' => 'drunken',
    ],
    'workers-dir' => __DIR__ . '/src/drunken_workers',
    'log_path' => __DIR__ . '/var/drunken.log',
    'hipchat' => [
        'token' => '',
        'room' => ''
    ],
];

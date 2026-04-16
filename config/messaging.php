<?php

return [
    'default' => env('MESSAGING_BROKER', 'redis'),
    'redis' => [
        'host' => env('MESSAGING_REDIS_HOST'),
        'port' => env('MESSAGING_REDIS_PORT'),
    ],
];

<?php

return [
    'store' => env('LOCK_STORE', 'redis'),
    'prefix' => env('LOCK_PREFIX', 'lock'),
    'ttl_seconds' => (int) env('LOCK_TTL_SECONDS', 5),
    'wait_seconds' => (int) env('LOCK_WAIT_SECONDS', 1),
];

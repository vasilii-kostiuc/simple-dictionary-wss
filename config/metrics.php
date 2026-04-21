<?php

return [
    'enabled' => env('PROMETHEUS_ENABLED', true),
    'namespace' => env('PROMETHEUS_NAMESPACE', 'wss'),
    'host' => env('PROMETHEUS_HOST', '0.0.0.0'),
    'port' => (int) env('PROMETHEUS_PORT', 9091),
    'path' => env('PROMETHEUS_PATH', '/metrics'),
];

<?php

return [
    App\Providers\MessagingConfigServiceProvider::class,
    App\Providers\AppServiceProvider::class,
    App\Providers\ExternalServicesServiceProvider::class,
    App\Providers\DomainInfrastructureServiceProvider::class,
    App\Providers\MetricsServiceProvider::class,
    App\Providers\WebSocketRuntimeServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\WebSocketHandlersServiceProvider::class,
];

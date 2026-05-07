<?php

namespace App\Providers;

use App\Infrastructure\Metrics\NullWsMetrics;
use App\Infrastructure\Metrics\WsMetrics;
use App\Infrastructure\Metrics\WsMetricsInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;

class MetricsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CollectorRegistry::class, function () {
            return new CollectorRegistry(new InMemory);
        });

        $this->app->singleton(WsMetricsInterface::class, function (Application $app) {
            if (! config('metrics.enabled', true)) {
                return new NullWsMetrics;
            }

            return new WsMetrics(
                $app->make(CollectorRegistry::class),
                (string) config('metrics.namespace', 'wss'),
                (string) config('app.node_id'),
            );
        });
    }
}

<?php

namespace App\Providers;

use App\Application\Contracts\SimpleDictionaryApiClientInterface;
use App\Infrastructure\ApiClients\Fake\FakeSimpleDictionaryApiClient;
use App\Infrastructure\ApiClients\GuzzleSimpleDictionaryApiClient;
use GuzzleHttp\Client;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class ExternalServicesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Client::class, function () {
            return new Client([
                'base_uri' => config('services.api.base_uri', 'http://api:8876/api/v1'),
            ]);
        });

        $this->app->singleton(SimpleDictionaryApiClientInterface::class, function (Application $app) {
            if ($app->environment('testing')) {
                return new FakeSimpleDictionaryApiClient;
            }

            return new GuzzleSimpleDictionaryApiClient(
                $app->make(Client::class),
                config('services.api.wss_token'),
            );
        });
    }
}

<?php

namespace App\Providers;

use App\ApiClients\Fake\FakeSimpleDictionaryApiClient;
use App\ApiClients\SimpleDictionaryApiClientInterface;
use App\WebSockets\Storage\Clients\AuthorizedClientsStorage;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorage;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use GuzzleHttp\Client;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Client::class, function () {
            return new Client([
                'base_uri' => config('services.api.base_uri', 'http://api:8876/api/v1'),
            ]);
        });

        $this->app->singleton(ClientsStorageInterface::class, function () {
            return new AuthorizedClientsStorage();
        });

        $this->app->singleton(SubscriptionsStorageInterface::class, function () {
            return new SubscriptionsStorage();
        });

        $this->app->singleton(\App\WebSockets\Storage\Timers\TrainingTimerStorageInterface::class, function () {
            return new \App\WebSockets\Storage\Timers\MongoTrainingTimerStorage();
        });

        $this->app->singleton(SimpleDictionaryApiClientInterface::class, function (Application $app) {
            info('Environment: ' . $app->environment() . '');
            if ($app->environment('testing')) {
                return new FakeSimpleDictionaryApiClient();
            }

            $defaultConfig['headers']['Authorization'] = 'Bearer '. config('services.api.wss_token');

            return new \App\ApiClients\GuzzleSimpleDictionaryApiClient(
                $app->make(\GuzzleHttp\Client::class, $defaultConfig), config('services.api.wss_token')
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

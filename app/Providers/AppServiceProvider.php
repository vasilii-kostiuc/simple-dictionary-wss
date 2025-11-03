<?php

namespace App\Providers;

use App\WebSockets\Storage\AuthorizedClientsStorage;
use App\WebSockets\Storage\ClientsStorageInterface;
use App\WebSockets\Storage\SubscriptionsStorage;
use App\WebSockets\Storage\SubscriptionsStorageInterface;
use GuzzleHttp\Client;
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
                'base_uri' => config('services.api.base_uri', 'http://api:8876/api/v1'),            ]);
        });

        $this->app->singleton(ClientsStorageInterface::class, function () {
            return new AuthorizedClientsStorage();
        });

        $this->app->singleton(SubscriptionsStorageInterface::class, function () {
            return new SubscriptionsStorage();
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

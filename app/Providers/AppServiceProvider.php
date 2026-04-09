<?php

namespace App\Providers;

use App\Infrastructure\ApiClients\Fake\FakeSimpleDictionaryApiClient;
use App\Application\Contracts\SimpleDictionaryApiClientInterface;
use App\WebSockets\Identity\GuestIdentityGeneratorInterface;
use App\WebSockets\Identity\RandomGuestIdentityGenerator;
use App\WebSockets\Sender\WebSocketMessageSender;
use App\WebSockets\Sender\WebSocketMessageSenderInterface;
use App\WebSockets\Storage\Clients\AuthorizedClientsStorage;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use App\WebSockets\Storage\Clients\CompositeClientsStorage;
use App\WebSockets\Storage\Clients\GuestClientsStorage;
use App\Domain\MatchMaking\Contracts\MatchMakingQueueInterface;
use App\Infrastructure\MatchMaking\RedisMatchMakingQueue;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorage;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use GuzzleHttp\Client;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;

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

        $this->app->singleton(AuthorizedClientsStorage::class, function () {
            return new AuthorizedClientsStorage;
        });

        $this->app->singleton(GuestClientsStorage::class, function () {
            return new GuestClientsStorage;
        });

        $this->app->singleton(ClientsStorageInterface::class, function (Application $app) {
            return new CompositeClientsStorage(
                $app->make(AuthorizedClientsStorage::class),
                $app->make(GuestClientsStorage::class),
            );
        });

        $this->app->singleton(WebSocketMessageSenderInterface::class, function (Application $app) {
            return new WebSocketMessageSender($app->make(ClientsStorageInterface::class));
        });

        $this->app->singleton(SubscriptionsStorageInterface::class, function () {
            return new SubscriptionsStorage;
        });

        $this->app->singleton(LoopInterface::class, function () {
            return Loop::get();
        });

        $this->app->singleton(MatchMakingQueueInterface::class, function () {
            return new RedisMatchMakingQueue;
        });

        $this->app->singleton(GuestIdentityGeneratorInterface::class, function () {
            return new RandomGuestIdentityGenerator;
        });

        $this->app->singleton(\App\Domain\Shared\Contracts\TimerStorageInterface::class, function () {
            return new \App\Infrastructure\Training\MongoTimerStorage;
        });

        $this->app->singleton(\VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerInterface::class, function () {
            return \App::make(\VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerFactory::class)->create();
        });

        $this->app->singleton(SimpleDictionaryApiClientInterface::class, function (Application $app) {
            info('Environment: '.$app->environment().'');
            if ($app->environment('testing')) {
                return new FakeSimpleDictionaryApiClient;
            }

            $defaultConfig['headers']['Authorization'] = 'Bearer '.config('services.api.wss_token');

            return new \App\Infrastructure\ApiClients\GuzzleSimpleDictionaryApiClient(
                $app->make(\GuzzleHttp\Client::class, $defaultConfig), config('services.api.wss_token')
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app['events']->listen(
            \App\Application\MatchMaking\Events\MatchMakingJoinedEvent::class,
            \App\WebSockets\Listeners\MatchMaking\PublishMatchMakingJoinedListener::class
        );

        $this->app['events']->listen(
            \App\Application\MatchMaking\Events\MatchMakingLeaveEvent::class,
            \App\WebSockets\Listeners\MatchMaking\PublishMatchMakingLeaveListener::class
        );

        $this->app['events']->listen(
            \App\Application\MatchMaking\Events\MatchMakingQueueUpdatedEvent::class,
            \App\WebSockets\Listeners\MatchMaking\PublishMatchMakingQueueUpdatedListener::class
        );
    }
}

<?php

namespace App\Providers;

use App\Infrastructure\ApiClients\Fake\FakeSimpleDictionaryApiClient;
use App\Application\Contracts\SimpleDictionaryApiClientInterface;
use App\Domain\Shared\Identity\GuestIdentityFactoryInterface;
use App\Domain\Shared\Identity\UserIdentityResolverInterface;
use App\Infrastructure\Identity\RandomGuestIdentityFactory;
use App\Infrastructure\Identity\SimpleDictionaryApiUserIdentityResolver;
use App\WebSockets\Sender\WebSocketMessageSender;
use App\WebSockets\Sender\WebSocketMessageSenderInterface;
use App\WebSockets\Storage\Clients\AuthorizedClientRegistry;
use App\WebSockets\Storage\Clients\ClientRegistryInterface;
use App\WebSockets\Storage\Clients\CompositeClientRegistry;
use App\WebSockets\Storage\Clients\GuestClientRegistry;
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

        $this->app->singleton(AuthorizedClientRegistry::class, function () {
            return new AuthorizedClientRegistry;
        });

        $this->app->singleton(GuestClientRegistry::class, function () {
            return new GuestClientRegistry;
        });

        $this->app->singleton(ClientRegistryInterface::class, function (Application $app) {
            return new CompositeClientRegistry(
                $app->make(AuthorizedClientRegistry::class),
                $app->make(GuestClientRegistry::class),
            );
        });

        $this->app->singleton(WebSocketMessageSenderInterface::class, function (Application $app) {
            return new WebSocketMessageSender($app->make(ClientRegistryInterface::class));
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

        $this->app->singleton(GuestIdentityFactoryInterface::class, function () {
            return new RandomGuestIdentityFactory;
        });

        $this->app->singleton(UserIdentityResolverInterface::class, function (Application $app) {
            return new SimpleDictionaryApiUserIdentityResolver(
                $app->make(SimpleDictionaryApiClientInterface::class),
            );
        });

        $this->app->singleton(\App\Domain\Shared\Contracts\TimerStorageInterface::class, function () {
            return new \App\Infrastructure\Shared\MongoTimerStorage;
        });

        $this->app->singleton(\VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerInterface::class, function () {
            return \App::make(\VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerFactory::class)->create();
        });

        $this->app->singleton(SimpleDictionaryApiClientInterface::class, function (Application $app) {
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

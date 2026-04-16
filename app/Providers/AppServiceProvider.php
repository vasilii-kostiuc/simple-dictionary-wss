<?php

namespace App\Providers;

use App\Application\Contracts\EventDispatcherInterface;
use App\Application\Contracts\SimpleDictionaryApiClientInterface;
use App\Domain\LinkMatchRoom\LinkMatchRoomRepositoryInterface;
use App\Domain\MatchMaking\Contracts\MatchMakingQueueInterface;
use App\Domain\Shared\Identity\ClientIdentityLookupInterface;
use App\Domain\Shared\Identity\GuestIdentityFactoryInterface;
use App\Domain\Shared\Identity\UserIdentityResolverInterface;
use App\Infrastructure\ApiClients\Fake\FakeSimpleDictionaryApiClient;
use App\Infrastructure\Identity\RandomGuestIdentityFactory;
use App\Infrastructure\Identity\SimpleDictionaryApiUserIdentityResolver;
use App\Infrastructure\LinkMatchRoom\RedisLinkMatchRoomRepository;
use App\Infrastructure\MatchMaking\RedisMatchMakingQueue;
use App\Infrastructure\Shared\LaravelEventDispatcher;
use App\WebSockets\Sender\WebSocketMessageSender;
use App\WebSockets\Sender\WebSocketMessageSenderInterface;
use App\WebSockets\Storage\Clients\AuthorizedClientRegistry;
use App\WebSockets\Storage\Clients\ClientRegistryInterface;
use App\WebSockets\Storage\Clients\CompositeClientRegistry;
use App\WebSockets\Storage\Clients\GuestClientRegistry;
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
        config([
            'messaging.redis.host' => config('messaging.redis.host') ?: config('database.redis.default.host'),
            'messaging.redis.port' => config('messaging.redis.port') ?: config('database.redis.default.port'),
        ]);

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

        $this->app->singleton(ClientIdentityLookupInterface::class, function (Application $app) {
            return $app->make(ClientRegistryInterface::class);
        });

        $this->app->singleton(WebSocketMessageSenderInterface::class, function (Application $app) {
            return new WebSocketMessageSender(
                $app->make(ClientRegistryInterface::class),
                $app->make(\VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerInterface::class),
            );
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

        $this->app->singleton(LinkMatchRoomRepositoryInterface::class, function () {
            return new RedisLinkMatchRoomRepository;
        });

        $this->app->singleton(\App\Application\Contracts\LockManagerInterface::class, function (Application $app) {
            return new \App\Infrastructure\Shared\CacheLockManager($app->make(\Illuminate\Contracts\Cache\Factory::class));
        });

        $this->app->bind(EventDispatcherInterface::class, LaravelEventDispatcher::class);

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

        $this->app['events']->listen(
            \App\Domain\LinkMatchRoom\Events\RoomBecameFullEvent::class,
            \App\Application\LinkMatchRoom\Listeners\CreateMatchOnRoomFullListener::class
        );

        $this->app['events']->listen(
            \App\Domain\LinkMatchRoom\Events\ParticipantJoinedEvent::class,
            \App\WebSockets\Listeners\LinkMatchRoom\PublishLinkMatchRoomJoinedListener::class
        );

        $this->app['events']->listen(
            \App\Domain\LinkMatchRoom\Events\ParticipantLeftEvent::class,
            \App\WebSockets\Listeners\LinkMatchRoom\PublishLinkMatchRoomLeftListener::class
        );
    }
}

<?php

namespace App\Providers;

use App\Application\LinkMatchRoom\Actions\DisconnectFromLinkMatchRoomAction;
use App\Application\MatchMaking\Actions\LeaveMatchMakingAction;
use App\Domain\MatchMaking\Contracts\MatchMakingQueueInterface;
use App\Domain\Shared\Identity\ClientIdentityLookupInterface;
use App\Infrastructure\Metrics\WsMetricsInterface;
use App\WebSockets\Dispatch\ClientMessageDispatcher;
use App\WebSockets\Handlers\Client\MessageHandlerFactory;
use App\WebSockets\Lifecycle\ConnectionLifecycleService;
use App\WebSockets\MetricsWsServerDecorator;
use App\WebSockets\Sender\WebSocketMessageSender;
use App\WebSockets\Sender\WebSocketMessageSenderInterface;
use App\WebSockets\Storage\Clients\AuthorizedClientRegistry;
use App\WebSockets\Storage\Clients\ClientRegistryInterface;
use App\WebSockets\Storage\Clients\CompositeClientRegistry;
use App\WebSockets\Storage\Clients\GuestClientRegistry;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorage;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use App\WebSockets\TrainingWsServer;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Ratchet\WebSocket\MessageComponentInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerFactory;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerInterface;

class WebSocketRuntimeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
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
                $app->make(MessageBrokerInterface::class),
            );
        });

        $this->app->singleton(SubscriptionsStorageInterface::class, function () {
            return new SubscriptionsStorage;
        });

        $this->app->singleton(LoopInterface::class, function () {
            return Loop::get();
        });

        $this->app->singleton(MessageBrokerInterface::class, function () {
            return App::make(MessageBrokerFactory::class)->create();
        });

        $this->app->singleton(MessageComponentInterface::class, function (Application $app) {
            return new MetricsWsServerDecorator(
                $app->make(TrainingWsServer::class),
                $app->make(WsMetricsInterface::class),
            );
        });

        $this->app->singleton(ClientMessageDispatcher::class, function (Application $app) {
            return new ClientMessageDispatcher(
                $app->make(MessageHandlerFactory::class),
                $app->make(WsMetricsInterface::class),
                (string) config('app.node_id'),
            );
        });

        $this->app->singleton(ConnectionLifecycleService::class, function (Application $app) {
            return new ConnectionLifecycleService(
                $app->make(ClientRegistryInterface::class),
                $app->make(SubscriptionsStorageInterface::class),
                $app->make(DisconnectFromLinkMatchRoomAction::class),
                $app->make(LeaveMatchMakingAction::class),
                $app->make(MatchMakingQueueInterface::class),
                $app->make(WsMetricsInterface::class),
                (string) config('app.node_id'),
            );
        });
    }
}

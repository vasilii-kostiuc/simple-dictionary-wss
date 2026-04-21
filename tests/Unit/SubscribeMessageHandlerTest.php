<?php

namespace Tests\Unit;

use App\Infrastructure\Metrics\WsMetrics;
use App\WebSockets\Handlers\Client\Subscription\SubscribeMessageHandler;
use App\WebSockets\Messages\Subscription\SubscribeSuccessMessage;
use App\WebSockets\Storage\Clients\ClientRegistryInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use App\WebSockets\Subscription\SubscriptionChannelPolicy;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class SubscribeMessageHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container;
        $container->instance('log', new class
        {
            public function info(...$args): void {}
        });

        Container::setInstance($container);
        Facade::setFacadeApplication($container);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);

        parent::tearDown();
    }

    public function test_tracks_metrics_for_valid_subscribe(): void
    {
        $subscriptionsStorage = $this->createMock(SubscriptionsStorageInterface::class);
        $clientRegistry = $this->createMock(ClientRegistryInterface::class);
        $metrics = $this->createMock(WsMetrics::class);
        $connection = $this->createMock(ConnectionInterface::class);
        $message = $this->createMock(MessageInterface::class);

        $message->method('getPayload')->willReturn(json_encode([
            'type' => 'subscribe',
            'data' => [
                'channel' => 'training.121',
            ],
        ]));

        $subscriptionsStorage->expects($this->once())
            ->method('subscribe')
            ->with($connection, 'training.121');

        $metrics->expects($this->once())
            ->method('subscribed')
            ->with('training.121');

        $connection->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($sentMessage): bool {
                return $sentMessage instanceof SubscribeSuccessMessage
                    && $sentMessage->type === 'subscribe_success'
                    && $sentMessage->data['channel'] === 'training.121';
            }));

        (new SubscribeMessageHandler(
            $subscriptionsStorage,
            $clientRegistry,
            new SubscriptionChannelPolicy,
            $metrics,
        ))->handle($connection, $message);
    }
}

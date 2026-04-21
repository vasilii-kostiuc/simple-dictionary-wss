<?php

namespace Tests\Unit;

use App\Infrastructure\Metrics\WsMetrics;
use App\WebSockets\Handlers\Client\Subscription\UnsubscribeMessageHandler;
use App\WebSockets\Messages\Subscription\UnsubscribeSuccessMessage;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use App\WebSockets\Subscription\SubscriptionChannelPolicy;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class UnsubscribeMessageHandlerTest extends TestCase
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

    public function test_allows_unsubscribe_for_match_channel(): void
    {
        $subscriptionsStorage = $this->createMock(SubscriptionsStorageInterface::class);
        $metrics = $this->createMock(WsMetrics::class);
        $connection = $this->createMock(ConnectionInterface::class);
        $message = $this->createMock(MessageInterface::class);

        $message->method('getPayload')->willReturn(json_encode([
            'type' => 'unsubscribe',
            'data' => [
                'channel' => 'match.123',
            ],
        ]));

        $subscriptionsStorage->expects($this->once())
            ->method('unsubscribe')
            ->with($connection, 'match.123')
            ->willReturn(true);

        $metrics->expects($this->once())
            ->method('subscriptionAttempted')
            ->with('match.123', 'unsubscribe', 'success');

        $metrics->expects($this->once())
            ->method('activeSubscriptionRemoved')
            ->with('match.123');

        $connection->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($sentMessage): bool {
                return $sentMessage instanceof UnsubscribeSuccessMessage
                    && $sentMessage->type === 'unsubscribe_success'
                    && $sentMessage->data['channel'] === 'match.123';
            }));

        (new UnsubscribeMessageHandler($subscriptionsStorage, new SubscriptionChannelPolicy, $metrics))->handle($connection, $message);
    }
}

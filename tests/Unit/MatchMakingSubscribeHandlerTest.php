<?php

namespace Tests\Unit;

use App\Domain\MatchMaking\Contracts\MatchMakingQueueInterface;
use App\Infrastructure\Metrics\WsMetricsInterface;
use App\WebSockets\Handlers\Client\MatchMaking\MatchMakingSubscribeHandler;
use App\WebSockets\Messages\MatchMaking\MatchMakingQueueUpdatedMessage;
use App\WebSockets\Messages\Subscription\SubscribeSuccessMessage;
use App\WebSockets\Sender\WebSocketMessageSenderInterface;
use App\WebSockets\Storage\Clients\ClientRegistryInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use App\WebSockets\Subscription\SubscriptionChannelPolicy;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class MatchMakingSubscribeHandlerTest extends TestCase
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

    public function test_subscribes_and_sends_current_queue_snapshot(): void
    {
        $subscriptionsStorage = $this->createMock(SubscriptionsStorageInterface::class);
        $clientRegistry = $this->createMock(ClientRegistryInterface::class);
        $metrics = $this->createMock(WsMetricsInterface::class);
        $matchMakingQueue = $this->createMock(MatchMakingQueueInterface::class);
        $sender = $this->createMock(WebSocketMessageSenderInterface::class);
        $connection = $this->createMock(ConnectionInterface::class);
        $message = $this->createMock(MessageInterface::class);

        $queueEntry = new class
        {
            public function toArray(): array
            {
                return ['identifier' => 'user-1'];
            }
        };

        $message->method('getPayload')->willReturn(json_encode([
            'type' => 'subscribe',
            'data' => [
                'channel' => 'matchmaking.queue',
            ],
        ]));

        $subscriptionsStorage->expects($this->once())
            ->method('subscribe')
            ->with($connection, 'matchmaking.queue')
            ->willReturn(true);

        $metrics->expects($this->once())
            ->method('subscriptionAttempted')
            ->with('matchmaking.queue', 'subscribe', 'success');

        $metrics->expects($this->once())
            ->method('activeSubscriptionAdded')
            ->with('matchmaking.queue');

        $connection->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($sentMessage): bool {
                return $sentMessage instanceof SubscribeSuccessMessage
                    && $sentMessage->type === 'subscribe_success'
                    && $sentMessage->data['channel'] === 'matchmaking.queue';
            }));

        $matchMakingQueue->expects($this->once())
            ->method('allQueues')
            ->willReturn([$queueEntry]);

        $sender->expects($this->once())
            ->method('sendToConnection')
            ->with($connection, $this->callback(function ($message): bool {
                return $message instanceof MatchMakingQueueUpdatedMessage
                    && $message->type === 'matchmaking.queue.updated';
            }));

        (new MatchMakingSubscribeHandler(
            $subscriptionsStorage,
            $clientRegistry,
            new SubscriptionChannelPolicy,
            $metrics,
            $matchMakingQueue,
            $sender,
        ))->handle($connection, $message);
    }
}

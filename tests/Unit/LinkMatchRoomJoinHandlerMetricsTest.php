<?php

namespace Tests\Unit;

use App\Application\LinkMatchRoom\Actions\JoinLinkMatchRoomAction;
use App\Domain\Shared\Identity\ClientIdentity;
use App\Infrastructure\Metrics\WsMetrics;
use App\WebSockets\Handlers\Client\LinkMatchRoom\LinkMatchRoomJoinHandler;
use App\WebSockets\Messages\MatchRoom\MatchRoomChangedMessage;
use App\WebSockets\Sender\WebSocketMessageSenderInterface;
use App\WebSockets\Storage\Clients\ClientRegistryInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class LinkMatchRoomJoinHandlerMetricsTest extends TestCase
{
    public function test_tracks_metrics_for_successful_link_match_room_join(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $message = $this->createMock(MessageInterface::class);
        $clientRegistry = $this->createMock(ClientRegistryInterface::class);
        $joinAction = $this->createMock(JoinLinkMatchRoomAction::class);
        $sender = $this->createMock(WebSocketMessageSenderInterface::class);
        $subscriptionsStorage = $this->createMock(SubscriptionsStorageInterface::class);
        $metrics = $this->createMock(WsMetrics::class);

        $identity = new ClientIdentity(1, 'Alice', 'alice@example.com', null);
        $room = $this->createMock(\App\Domain\LinkMatchRoom\LinkMatchRoom::class);

        $message->method('getPayload')->willReturn(json_encode([
            'type' => 'link_match_room.join',
            'data' => ['link_token' => 'tok_abc'],
        ]));

        $clientRegistry->method('getIdentity')->with($connection)->willReturn($identity);
        $room->method('getId')->willReturn('room-1');
        $room->method('getParticipantIdentities')->willReturn([]);

        $joinAction->expects($this->once())
            ->method('execute')
            ->with($identity, ['link_token' => 'tok_abc'])
            ->willReturn(['room' => $room]);

        $subscriptionsStorage->expects($this->once())
            ->method('subscribe')
            ->with($connection, 'link_match_room.room-1');

        $metrics->expects($this->once())
            ->method('subscribed')
            ->with('link_match_room.room-1');

        $sender->expects($this->once())
            ->method('sendToConnection')
            ->with($connection, $this->isInstanceOf(MatchRoomChangedMessage::class));

        (new LinkMatchRoomJoinHandler(
            $clientRegistry,
            $joinAction,
            $sender,
            $subscriptionsStorage,
            $metrics,
        ))->handle($connection, $message);
    }
}

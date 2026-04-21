<?php

namespace Tests\Unit;

use App\Application\LinkMatchRoom\Actions\LeaveLinkMatchRoomAction;
use App\Application\LinkMatchRoom\Exceptions\LinkMatchRoomException;
use App\Domain\Shared\Identity\ClientIdentity;
use App\Infrastructure\Metrics\WsMetrics;
use App\WebSockets\Handlers\Client\LinkMatchRoom\LinkMatchRoomLeaveHandler;
use App\WebSockets\Messages\ErrorMessage;
use App\WebSockets\Messages\MatchRoom\MatchRoomChangedMessage;
use App\WebSockets\Sender\WebSocketMessageSenderInterface;
use App\WebSockets\Storage\Clients\ClientRegistryInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class LinkMatchRoomLeaveHandlerTest extends TestCase
{
    private ConnectionInterface $connection;

    private MessageInterface $message;

    private ClientRegistryInterface $clientRegistry;

    private LeaveLinkMatchRoomAction $leaveAction;

    private WebSocketMessageSenderInterface $sender;

    private ClientIdentity $identity;

    private SubscriptionsStorageInterface $subscriptionsStorage;

    private WsMetrics $metrics;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createMock(ConnectionInterface::class);
        $this->message = $this->createMock(MessageInterface::class);
        $this->clientRegistry = $this->createMock(ClientRegistryInterface::class);
        $this->leaveAction = $this->createMock(LeaveLinkMatchRoomAction::class);
        $this->sender = $this->createMock(WebSocketMessageSenderInterface::class);

        $this->identity = new ClientIdentity(1, 'Alice', 'alice@example.com', null);
        $this->clientRegistry->method('getIdentity')->willReturn($this->identity);
        $this->subscriptionsStorage = $this->createMock(SubscriptionsStorageInterface::class);
        $this->metrics = $this->createMock(WsMetrics::class);
    }

    private function handler(): LinkMatchRoomLeaveHandler
    {
        return new LinkMatchRoomLeaveHandler($this->clientRegistry, $this->leaveAction, $this->sender, $this->subscriptionsStorage, $this->metrics);
    }

    private function makeRoomMock(string $id, array $participantIdentities = []): \App\Domain\LinkMatchRoom\LinkMatchRoom
    {
        $room = $this->createMock(\App\Domain\LinkMatchRoom\LinkMatchRoom::class);
        $room->method('getId')->willReturn($id);
        $room->method('getParticipantIdentities')->willReturn($participantIdentities);

        return $room;
    }

    public function test_sends_leave_success_message_on_valid_leave(): void
    {
        $this->message->method('getPayload')->willReturn(json_encode([
            'type' => 'link_match_room.leave',
            'data' => ['link_token' => 'tok_abc'],
        ]));

        $room = $this->makeRoomMock('tok_abc', []);

        $this->leaveAction->expects($this->once())
            ->method('execute')
            ->with($this->identity, ['link_token' => 'tok_abc'])
            ->willReturn(['room' => $room]);

        $this->subscriptionsStorage->expects($this->once())
            ->method('unsubscribe')
            ->with($this->connection, 'link_match_room.tok_abc')
            ->willReturn(true);

        $this->metrics->expects($this->once())
            ->method('subscriptionAttempted')
            ->with('link_match_room.tok_abc', 'unsubscribe', 'success');

        $this->metrics->expects($this->once())
            ->method('activeSubscriptionRemoved')
            ->with('link_match_room.tok_abc');

        $this->sender->expects($this->once())
            ->method('sendToConnection')
            ->with($this->connection, $this->callback(function ($msg): bool {
                return $msg instanceof MatchRoomChangedMessage
                    && $msg->type === 'match_room.changed';
            }));

        $this->handler()->handle($this->connection, $this->message);
    }

    public function test_sends_error_message_when_link_not_found(): void
    {
        $this->message->method('getPayload')->willReturn(json_encode([
            'type' => 'link_match_room.leave',
            'data' => ['link_token' => 'bad_token'],
        ]));

        $this->leaveAction->expects($this->once())
            ->method('execute')
            ->willThrowException(new LinkMatchRoomException('link_not_found'));

        $this->metrics->expects($this->never())->method('subscriptionAttempted');
        $this->metrics->expects($this->never())->method('activeSubscriptionRemoved');

        $this->sender->expects($this->once())
            ->method('sendToConnection')
            ->with($this->connection, $this->callback(function ($msg): bool {
                return $msg instanceof ErrorMessage
                    && $msg->type === 'error'
                    && $msg->data['error'] === 'link_not_found';
            }));

        $this->handler()->handle($this->connection, $this->message);
    }

    public function test_sends_error_message_when_room_not_found(): void
    {
        $this->message->method('getPayload')->willReturn(json_encode([
            'type' => 'link_match_room.leave',
            'data' => ['link_token' => 'tok_abc'],
        ]));

        $this->leaveAction->expects($this->once())
            ->method('execute')
            ->willThrowException(new LinkMatchRoomException('link_match_room_not_found'));

        $this->metrics->expects($this->never())->method('subscriptionAttempted');
        $this->metrics->expects($this->never())->method('activeSubscriptionRemoved');

        $this->sender->expects($this->once())
            ->method('sendToConnection')
            ->with($this->connection, $this->callback(function ($msg): bool {
                return $msg instanceof ErrorMessage
                    && $msg->data['error'] === 'link_match_room_not_found';
            }));

        $this->handler()->handle($this->connection, $this->message);
    }

    public function test_sends_error_message_when_user_not_in_room(): void
    {
        $this->message->method('getPayload')->willReturn(json_encode([
            'type' => 'link_match_room.leave',
            'data' => ['link_token' => 'tok_abc'],
        ]));

        $this->leaveAction->expects($this->once())
            ->method('execute')
            ->willThrowException(new LinkMatchRoomException('not_in_room'));

        $this->metrics->expects($this->never())->method('subscriptionAttempted');
        $this->metrics->expects($this->never())->method('activeSubscriptionRemoved');

        $this->sender->expects($this->once())
            ->method('sendToConnection')
            ->with($this->connection, $this->callback(function ($msg): bool {
                return $msg instanceof ErrorMessage
                    && $msg->data['error'] === 'not_in_room';
            }));

        $this->handler()->handle($this->connection, $this->message);
    }

    public function test_sends_error_when_link_token_is_missing(): void
    {
        $this->message->method('getPayload')->willReturn(json_encode([
            'type' => 'link_match_room.leave',
            'data' => [],
        ]));

        $this->leaveAction->expects($this->once())
            ->method('execute')
            ->willThrowException(new LinkMatchRoomException('link_not_found', 'link_token is required'));

        $this->metrics->expects($this->never())->method('subscriptionAttempted');
        $this->metrics->expects($this->never())->method('activeSubscriptionRemoved');

        $this->sender->expects($this->once())
            ->method('sendToConnection')
            ->with($this->connection, $this->callback(function ($msg): bool {
                return $msg instanceof ErrorMessage
                    && $msg->data['error'] === 'link_not_found';
            }));

        $this->handler()->handle($this->connection, $this->message);
    }
}

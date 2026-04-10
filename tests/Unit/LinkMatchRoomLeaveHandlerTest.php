<?php

namespace Tests\Unit;

use App\Application\LinkMatchRoom\Actions\LeaveLinkMatchRoomAction;
use App\Application\LinkMatchRoom\Exceptions\LinkMatchRoomException;
use App\Domain\Shared\Identity\ClientIdentity;
use App\WebSockets\Handlers\Client\LinkMatchRoom\LinkMatchRoomLeaveHandler;
use App\WebSockets\Messages\ErrorMessage;
use App\WebSockets\Messages\LinkMatchRoom\LinkMatchRoomLeaveSuccessMessage;
use App\WebSockets\Sender\WebSocketMessageSenderInterface;
use App\WebSockets\Storage\Clients\ClientRegistryInterface;
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
    }

    private function handler(): LinkMatchRoomLeaveHandler
    {
        return new LinkMatchRoomLeaveHandler($this->clientRegistry, $this->leaveAction, $this->sender);
    }

    public function test_sends_leave_success_message_on_valid_leave(): void
    {
        $this->message->method('getPayload')->willReturn(json_encode([
            'type' => 'link_match_room.leave',
            'data' => ['link_token' => 'tok_abc'],
        ]));

        $this->leaveAction->expects($this->once())
            ->method('execute')
            ->with($this->identity, ['link_token' => 'tok_abc']);

        $this->sender->expects($this->once())
            ->method('sendToConnection')
            ->with($this->connection, $this->callback(function ($msg): bool {
                return $msg instanceof LinkMatchRoomLeaveSuccessMessage
                    && $msg->type === 'link_match_room_leave_success';
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

        $this->sender->expects($this->once())
            ->method('sendToConnection')
            ->with($this->connection, $this->callback(function ($msg): bool {
                return $msg instanceof ErrorMessage
                    && $msg->data['error'] === 'link_not_found';
            }));

        $this->handler()->handle($this->connection, $this->message);
    }
}

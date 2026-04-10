<?php

namespace App\WebSockets\Handlers\Client\LinkMatchRoom;

use App\Application\LinkMatchRoom\Actions\JoinLinkMatchRoomAction;
use App\Application\LinkMatchRoom\Exceptions\LinkMatchRoomException;
use App\WebSockets\Handlers\Client\MessageHandlerInterface;
use App\WebSockets\Messages\ErrorMessage;
use App\WebSockets\Messages\LinkMatchRoom\LinkMatchRoomJoinSuccessMessage;
use App\WebSockets\Sender\WebSocketMessageSenderInterface;
use App\WebSockets\Storage\Clients\ClientRegistryInterface;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class LinkMatchRoomJoinHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly ClientRegistryInterface $clientRegistry,
        private readonly JoinLinkMatchRoomAction $joinAction,
        private readonly WebSocketMessageSenderInterface $sender,
    ) {
    }

    public function handle(ConnectionInterface $from, MessageInterface $msg): void
    {
        $payload = json_decode($msg->getPayload(), true);
        $identity = $this->clientRegistry->getIdentity($from);

        try {
            $result = $this->joinAction->execute($identity, $payload['data'] ?? []);
            $this->sender->sendToConnection($from, new LinkMatchRoomJoinSuccessMessage(
                $result['room']->getParticipants(),
            ));
        } catch (LinkMatchRoomException $e) {
            $this->sender->sendToConnection($from, new ErrorMessage($e->getErrorCode(), $payload ?? []));
        }
    }
}

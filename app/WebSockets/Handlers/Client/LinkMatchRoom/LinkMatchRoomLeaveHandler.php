<?php

namespace App\WebSockets\Handlers\Client\LinkMatchRoom;

use App\Application\LinkMatchRoom\Actions\LeaveLinkMatchRoomAction;
use App\Application\LinkMatchRoom\Exceptions\LinkMatchRoomException;
use App\WebSockets\Handlers\Client\MessageHandlerInterface;
use App\WebSockets\Messages\ErrorMessage;
use App\WebSockets\Messages\MatchRoom\MatchRoomChangedMessage;
use App\WebSockets\Sender\WebSocketMessageSenderInterface;
use App\WebSockets\Storage\Clients\ClientRegistryInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class LinkMatchRoomLeaveHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly ClientRegistryInterface $clientRegistry,
        private readonly LeaveLinkMatchRoomAction $leaveAction,
        private readonly WebSocketMessageSenderInterface $sender,
        private readonly SubscriptionsStorageInterface $subscriptionsStorage,
    ) {
    }

    public function handle(ConnectionInterface $from, MessageInterface $msg): void
    {
        $payload = json_decode($msg->getPayload(), true);
        $identity = $this->clientRegistry->getIdentity($from);
        $token = $payload['data']['link_token'] ?? null;

        try {
            $result = $this->leaveAction->execute($identity, $payload['data'] ?? []);
            $room = $result['room'];

            $this->subscriptionsStorage->unsubscribe($from, 'link_match_room.'.$room->getId());

            $this->sender->sendToConnection($from, new MatchRoomChangedMessage($room->getId(), [
                'participants' => array_map(fn ($p) => $p->toArray(), $room->getParticipantIdentities()),
            ]));
        } catch (LinkMatchRoomException $e) {
            $this->sender->sendToConnection($from, new ErrorMessage($e->getErrorCode(), $payload ?? []));
        }
    }
}

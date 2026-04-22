<?php

namespace App\WebSockets\Handlers\Client\LinkMatchRoom;

use App\Application\LinkMatchRoom\Actions\JoinLinkMatchRoomAction;
use App\Application\LinkMatchRoom\Exceptions\LinkMatchRoomException;
use App\Infrastructure\Metrics\WsMetricsInterface;
use App\WebSockets\Handlers\Client\MessageHandlerInterface;
use App\WebSockets\Messages\ErrorMessage;
use App\WebSockets\Messages\MatchRoom\MatchRoomChangedMessage;
use App\WebSockets\Sender\WebSocketMessageSenderInterface;
use App\WebSockets\Storage\Clients\ClientRegistryInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class LinkMatchRoomJoinHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly ClientRegistryInterface $clientRegistry,
        private readonly JoinLinkMatchRoomAction $joinAction,
        private readonly WebSocketMessageSenderInterface $sender,
        private readonly SubscriptionsStorageInterface $subscriptionsStorage,
        private readonly WsMetricsInterface $metrics,
    ) {}

    public function handle(ConnectionInterface $from, MessageInterface $msg): void
    {
        $payload = json_decode($msg->getPayload(), true);
        $identity = $this->clientRegistry->getIdentity($from);

        try {
            $result = $this->joinAction->execute($identity, $payload['data'] ?? []);
            $room = $result['room'];

            $channel = 'link_match_room.'.$room->getId();
            $changed = $this->subscriptionsStorage->subscribe($from, $channel);
            $this->metrics->subscriptionAttempted($channel, 'subscribe', $changed ? 'success' : 'noop');

            if ($changed) {
                $this->metrics->activeSubscriptionAdded($channel);
            }

            $this->sender->sendToConnection($from, new MatchRoomChangedMessage($room->getId(), [
                'participants' => array_map(fn ($p) => $p->toArray(), $room->getParticipantIdentities()),
            ]));
        } catch (LinkMatchRoomException $e) {
            $this->sender->sendToConnection($from, new ErrorMessage($e->getErrorCode(), $payload ?? []));
        }
    }
}

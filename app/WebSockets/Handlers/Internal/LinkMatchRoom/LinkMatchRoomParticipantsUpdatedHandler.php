<?php

namespace App\WebSockets\Handlers\Internal\LinkMatchRoom;

use App\Domain\LinkMatchRoom\LinkMatchRoomRepositoryInterface;
use App\WebSockets\Handlers\Internal\InternalMessageHandlerInterface;
use App\WebSockets\Messages\MatchRoom\MatchRoomChangedMessage;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;

class LinkMatchRoomParticipantsUpdatedHandler implements InternalMessageHandlerInterface
{
    public function __construct(
        private readonly SubscriptionsStorageInterface $subscriptionsStorage,
        private readonly LinkMatchRoomRepositoryInterface $linkMatchRoomRepository,
    ) {}

    public function handle(mixed $payload): void
    {
        $roomId = $payload['data']['room_id'] ?? null;
        $participants = $payload['data']['participants'] ?? [];

        if ($roomId === null) {
            return;
        }

        $room = $this->linkMatchRoomRepository->findByLinkMatchId($roomId);

        if ($room !== null) {
            $participants = array_map(fn ($p) => $p->toArray(), $room->getParticipantIdentities());
        }

        $channel = 'link_match_room.'.$roomId;

        foreach ($this->subscriptionsStorage->getConnectionsByChannel($channel) as $conn) {
            $conn->send(new MatchRoomChangedMessage($roomId, ['participants' => $participants]));
        }
    }
}

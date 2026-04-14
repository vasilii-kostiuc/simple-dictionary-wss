<?php

namespace App\WebSockets\Listeners\LinkMatchRoom;

use App\Domain\LinkMatchRoom\Events\ParticipantJoinedEvent;
use App\Domain\LinkMatchRoom\LinkMatchRoomRepositoryInterface;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerInterface;

class PublishLinkMatchRoomJoinedListener
{
    public function __construct(
        private readonly MessageBrokerInterface $messageBroker,
        private readonly LinkMatchRoomRepositoryInterface $roomRepository,
    ) {
    }

    public function handle(ParticipantJoinedEvent $event): void
    {
        $room = $this->roomRepository->findByLinkMatchId($event->roomId);
        $participants = $room !== null
            ? array_map(fn ($p) => $p->toArray(), $room->getParticipantIdentities())
            : array_map(fn (string $id) => ['id' => $id], $event->participants);

        $this->messageBroker->publish('wss.link_match_room.joined', json_encode([
            'type' => 'wss.link_match_room.joined',
            'data' => [
                'room_id' => $event->roomId,
                'participants' => $participants,
            ],
        ]));
    }
}

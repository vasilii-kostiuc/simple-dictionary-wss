<?php

namespace App\Application\LinkMatchRoom\Actions;

use App\Application\Contracts\EventDispatcherInterface;
use App\Application\Contracts\LockManagerInterface;
use App\Domain\LinkMatchRoom\LinkMatchRoomRepositoryInterface;
use App\Domain\Shared\Identity\ClientIdentity;

class DisconnectFromLinkMatchRoomAction
{
    public function __construct(
        private readonly LinkMatchRoomRepositoryInterface $roomRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LockManagerInterface $lockManager,
    ) {}

    public function execute(ClientIdentity $identity, string $roomId): void
    {
        $this->lockManager->execute('link_match_room:'.$roomId, function () use ($identity, $roomId) {
            $room = $this->roomRepository->findByLinkMatchId($roomId);

            if ($room === null) {
                return;
            }

            try {
                $room->leaveParticipant($identity);
            } catch (\DomainException) {
                return; // MatchCreating or MatchCreated — leave not allowed
            }

            if ($room->isEmpty()) {
                $this->roomRepository->deleteByLinkMatchId($room->getId());
            } else {
                $this->roomRepository->update($room);
            }

            foreach ($room->pullEvents() as $event) {
                $this->eventDispatcher->dispatch($event);
            }
        });
    }
}

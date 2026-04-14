<?php

namespace App\Application\LinkMatchRoom\Actions;

use App\Application\Contracts\EventDispatcherInterface;
use App\Application\Contracts\SimpleDictionaryApiClientInterface;
use App\Application\LinkMatchRoom\Exceptions\LinkMatchRoomException;
use App\Domain\LinkMatchRoom\LinkMatchRoomRepositoryInterface;
use App\Domain\Shared\Identity\ClientIdentity;

class LeaveLinkMatchRoomAction
{
    public function __construct(
        private readonly SimpleDictionaryApiClientInterface $apiClient,
        private readonly LinkMatchRoomRepositoryInterface $roomRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * @throws LinkMatchRoomException
     */
    public function execute(ClientIdentity $identity, array $data): array
    {
        $token = $data['link_token'] ?? null;

        if (! $token) {
            throw new LinkMatchRoomException('link_not_found', 'link_token is required');
        }

        $linkMatch = $this->apiClient->getLinkMatch($token);

        if ($linkMatch === null) {
            throw new LinkMatchRoomException('link_not_found', 'Link not found');
        }

        $room = $this->roomRepository->findByLinkMatchId($linkMatch->id);

        if ($room === null) {
            throw new LinkMatchRoomException('link_match_room_not_found', 'Room not found');
        }

        $room->leaveParticipant($identity);

        if ($room->isEmpty()) {
            $this->roomRepository->deleteByLinkMatchId($room->getId());
        } else {
            $this->roomRepository->update($room);
        }

        foreach ($room->pullEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }

        return ['room' => $room];
    }
}

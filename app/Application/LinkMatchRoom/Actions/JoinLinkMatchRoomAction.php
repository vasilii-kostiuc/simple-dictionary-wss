<?php

namespace App\Application\LinkMatchRoom\Actions;

use App\Application\Contracts\EventDispatcherInterface;
use App\Application\Contracts\LockManagerInterface;
use App\Application\Contracts\SimpleDictionaryApiClientInterface;
use App\Application\LinkMatchRoom\Exceptions\LinkMatchRoomException;
use App\Domain\LinkMatchRoom\LinkMatchRoomRepositoryInterface;
use App\Domain\Shared\Identity\ClientIdentity;

class JoinLinkMatchRoomAction
{
    public function __construct(
        private readonly SimpleDictionaryApiClientInterface $apiClient,
        private readonly LinkMatchRoomRepositoryInterface $roomRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LockManagerInterface $lockManager,
    ) {}

    public function execute(ClientIdentity $identity, array $data): array
    {
        $token = $data['link_token'] ?? null;

        if (! $token) {
            throw new LinkMatchRoomException('link_not_found', 'link_token is required');
        }

        $linkMatch = $this->apiClient->getLinkMatch($token);

        if ($linkMatch === null || ! $linkMatch->isActive()) {
            throw new LinkMatchRoomException('link_not_found', 'Link not found or inactive');
        }

        return $this->lockManager->execute('link_match_room:'.$linkMatch->id, function () use ($linkMatch, $identity) {
            $room = $this->roomRepository->getOrCreate($linkMatch);

            try {
                $room->joinParticipant($identity);
            } catch (\DomainException $e) {
                throw new LinkMatchRoomException('link_match_room_full', $e->getMessage());
            }

            $this->roomRepository->update($room);

            foreach ($room->pullEvents() as $event) {
                $this->eventDispatcher->dispatch($event);
            }

            return ['room' => $room];
        });
    }
}

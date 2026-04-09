<?php

namespace App\Application\MatchMaking\Actions;

use App\Application\Contracts\SimpleDictionaryApiClientInterface;
use App\Application\MatchMaking\Events\MatchMakingQueueUpdatedEvent;
use App\Application\MatchMaking\Exceptions\MatchMakingException;
use App\Domain\MatchMaking\Contracts\MatchMakingQueueInterface;
use App\Domain\Shared\Identity\ClientIdentity;

class ChallengeMatchMakingAction
{
    public function __construct(
        private readonly MatchMakingQueueInterface $matchMakingQueue,
        private readonly SimpleDictionaryApiClientInterface $apiClient,
    ) {
    }

    /**
     * @throws MatchMakingException
     */
    public function execute(ClientIdentity $identity, string $opponentId): array
    {
        if (! $this->matchMakingQueue->isUserInQueue($opponentId)) {
            throw new MatchMakingException('opponent_not_in_queue');
        }

        $matchData = $this->matchMakingQueue->extract($opponentId);

        if ($matchData === null) {
            throw new MatchMakingException('opponent_not_in_queue');
        }

        $currentParticipant = $identity->isGuest()
            ? ['id' => $identity->getIdentifier(), 'type' => 'guest', 'name' => $identity->name, 'avatar' => $identity->avatar]
            : ['id' => $identity->getIdentifier(), 'type' => 'user'];

        $opponentParticipant = ($matchData['guestId'] ?? null)
            ? ['id' => $matchData['guestId'], 'type' => 'guest', 'name' => $matchData['name'], 'avatar' => $matchData['avatar']]
            : ['id' => $matchData['userId'], 'type' => 'user'];

        $createResult = $this->apiClient->createMatch(
            [$currentParticipant, $opponentParticipant],
            $matchData['matchParams']
        );

        $this->matchMakingQueue->remove($identity->getIdentifier());

        event(new MatchMakingQueueUpdatedEvent);

        return $createResult ?? [];
    }
}

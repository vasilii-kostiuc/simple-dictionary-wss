<?php

namespace App\Application\MatchMaking\Actions;

use App\Application\Match\Actions\CreateMatchAction;
use App\Application\MatchMaking\Events\MatchMakingQueueUpdatedEvent;
use App\Application\MatchMaking\Exceptions\MatchMakingException;
use App\Domain\Match\MatchParticipant;
use App\Domain\MatchMaking\Contracts\MatchMakingQueueInterface;
use App\Domain\Shared\Identity\ClientIdentity;

class ChallengeMatchMakingAction
{
    public function __construct(
        private readonly MatchMakingQueueInterface $matchMakingQueue,
        private readonly CreateMatchAction $createMatchAction,
    ) {}

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

        $createResult = $this->createMatchAction->execute(
            [
                MatchParticipant::fromIdentity($identity),
                MatchParticipant::fromQueueData($matchData),
            ],
            $matchData['matchParams'],
        );

        $this->matchMakingQueue->remove($identity->getIdentifier());

        event(new MatchMakingQueueUpdatedEvent);

        return $createResult;
    }
}

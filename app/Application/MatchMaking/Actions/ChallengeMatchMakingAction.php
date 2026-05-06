<?php

namespace App\Application\MatchMaking\Actions;

use App\Application\Match\Actions\CreateMatchAction;
use App\Application\MatchMaking\Events\MatchMakingQueueUpdatedEvent;
use App\Application\MatchMaking\Exceptions\MatchMakingException;
use App\Domain\Match\MatchParticipant;
use App\Domain\MatchMaking\Contracts\MatchMakingQueueInterface;
use App\Domain\Shared\Identity\ClientIdentity;
use App\Infrastructure\Metrics\WsMetricsInterface;

class ChallengeMatchMakingAction
{
    public function __construct(
        private readonly MatchMakingQueueInterface $matchMakingQueue,
        private readonly CreateMatchAction $createMatchAction,
        private readonly WsMetricsInterface $metrics,
    ) {}

    /**
     * @throws MatchMakingException
     */
    public function execute(ClientIdentity $identity, string $opponentId): array
    {
        if (! $this->matchMakingQueue->isUserInQueue($opponentId)) {
            throw new MatchMakingException('opponent_not_in_queue');
        }

        $queueEntry = $this->matchMakingQueue->extract($opponentId);

        if ($queueEntry === null) {
            throw new MatchMakingException('opponent_not_in_queue');
        }

        $createResult = $this->createMatchAction->execute(
            [
                MatchParticipant::fromIdentity($identity),
                MatchParticipant::fromIdentity($queueEntry->identity),
            ],
            $queueEntry->matchParams,
        );

        $challengerWasInQueue = $this->matchMakingQueue->isUserInQueue($identity->getIdentifier());

        $this->matchMakingQueue->remove($identity->getIdentifier());

        // opponent was removed via extract(), challenger removed above
        $this->metrics->matchmakingQueueUserLeft();
        if ($challengerWasInQueue) {
            $this->metrics->matchmakingQueueUserLeft();
        }

        event(new MatchMakingQueueUpdatedEvent);

        return $createResult;
    }
}

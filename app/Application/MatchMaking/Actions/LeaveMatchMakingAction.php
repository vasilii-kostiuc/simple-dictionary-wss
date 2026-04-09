<?php

namespace App\Application\MatchMaking\Actions;

use App\Application\MatchMaking\Events\MatchMakingLeaveEvent;
use App\Domain\MatchMaking\Contracts\MatchMakingQueueInterface;

class LeaveMatchMakingAction
{
    public function __construct(
        private readonly MatchMakingQueueInterface $matchMakingQueue,
    ) {
    }

    public function execute(string $identifier): void
    {
        $this->matchMakingQueue->remove($identifier);

        event(new MatchMakingLeaveEvent($identifier));
    }
}

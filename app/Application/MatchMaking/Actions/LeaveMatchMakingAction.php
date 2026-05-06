<?php

namespace App\Application\MatchMaking\Actions;

use App\Application\MatchMaking\Events\MatchMakingLeaveEvent;
use App\Domain\MatchMaking\Contracts\MatchMakingQueueInterface;
use App\Infrastructure\Metrics\WsMetricsInterface;

class LeaveMatchMakingAction
{
    public function __construct(
        private readonly MatchMakingQueueInterface $matchMakingQueue,
        private readonly WsMetricsInterface $metrics,
    ) {}

    public function execute(string $identifier): void
    {
        $this->matchMakingQueue->remove($identifier);

        $this->metrics->matchmakingQueueUserLeft();

        event(new MatchMakingLeaveEvent($identifier));
    }
}

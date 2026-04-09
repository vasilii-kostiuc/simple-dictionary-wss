<?php

namespace App\Application\MatchMaking\Events;

class MatchMakingJoinedEvent
{
    public function __construct(
        public readonly string $userId,
        public readonly array $matchParams,
    ) {
    }
}

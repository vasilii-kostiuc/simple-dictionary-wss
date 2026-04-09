<?php

namespace App\Application\MatchMaking\Events;

class MatchMakingLeaveEvent
{
    public function __construct(
        public readonly string $userId
    ) {
    }
}

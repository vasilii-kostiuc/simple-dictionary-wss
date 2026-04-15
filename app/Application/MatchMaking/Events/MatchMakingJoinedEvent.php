<?php

namespace App\Application\MatchMaking\Events;

use App\Domain\Match\MatchParams;

class MatchMakingJoinedEvent
{
    public function __construct(
        public readonly string $userId,
        public readonly MatchParams $matchParams,
    ) {
    }
}

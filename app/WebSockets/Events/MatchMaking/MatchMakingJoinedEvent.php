<?php

namespace App\WebSockets\Events\MatchMaking;

class MatchMakingJoinedEvent
{
    public function __construct(
        public readonly string $userId,
        public readonly array $matchParams,
    ) {}
}

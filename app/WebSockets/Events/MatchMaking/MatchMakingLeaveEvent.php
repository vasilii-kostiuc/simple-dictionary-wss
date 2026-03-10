<?php

namespace App\WebSockets\Events\MatchMaking;

class MatchMakingLeaveEvent
{
    public function __construct(
        public readonly string $userId
    ) {}
}

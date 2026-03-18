<?php

namespace App\WebSockets\Messages\Match;

use App\WebSockets\Messages\WebSocketMessage;

class MatchStartedMessage extends WebSocketMessage
{
    public function __construct(protected array $matchData = [])
    {
        parent::__construct(
            'match_started',
            $this->matchData
        );
    }
}

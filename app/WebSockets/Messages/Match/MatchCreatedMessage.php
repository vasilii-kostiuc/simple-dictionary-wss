<?php

namespace App\WebSockets\Messages\Match;

use App\WebSockets\Messages\WebSocketMessage;

class MatchCreatedMessage extends WebSocketMessage
{
    public function __construct(protected array $matchData = [])
    {
        parent::__construct(
            'match_created',
            $this->matchData
        );
    }
}

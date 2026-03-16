<?php

namespace App\WebSockets\Messages\MatchMaking;

use App\WebSockets\Enums\MatchType;
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

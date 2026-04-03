<?php

namespace App\WebSockets\Messages\MatchMaking;

use App\WebSockets\Messages\WebSocketMessage;

class MatchMakingChallengeSuccessMessage extends WebSocketMessage
{
    public function __construct(array $matchMakingChallengeData = [])
    {
        parent::__construct('matchmaking_challenge_success', $matchMakingChallengeData);
    }
}


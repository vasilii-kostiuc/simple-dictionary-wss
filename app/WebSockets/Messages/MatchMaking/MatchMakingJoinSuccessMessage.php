<?php

namespace App\WebSockets\Messages\MatchMaking;

use App\WebSockets\Enums\MatchType;
use App\WebSockets\Messages\WebSocketMessage;

class MatchMakingJoinSuccessMessage extends WebSocketMessage
{
    public function __construct(protected MatchType $matchType, protected array $matchParams = [])
    {
        parent::__construct(
            'matchmaking_join_success',
            [
                'match_type' => $this->matchType->value,
                'match_params' => $this->matchParams,
            ]
        );

    }
}

<?php

namespace App\WebSockets\Messages\MatchMaking;

use App\Domain\Match\MatchParams;
use App\WebSockets\Messages\WebSocketMessage;

class MatchMakingJoinSuccessMessage extends WebSocketMessage
{
    public function __construct(protected MatchParams $matchParams)
    {
        parent::__construct(
            'matchmaking_join_success',
            [
                'match_type' => $this->matchParams->matchType->value,
                'match_params' => $this->matchParams->matchTypeParams,
            ]
        );
    }
}

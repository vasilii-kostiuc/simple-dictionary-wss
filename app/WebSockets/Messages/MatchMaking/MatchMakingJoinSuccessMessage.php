<?php

namespace App\WebSockets\Messages\MatchMaking;

use App\WebSockets\Enums\MatchType;

class MatchMakingJoinSuccessMessage
{
    public function __construct(protected MatchType $matchType, protected array $matchParams = [])
    {

    }

    public function toJson(): string
    {
        return json_encode([
            'type' => 'matchmaking_join_success',
            'match_type' => $this->matchType,
            'match_params' => $this->matchParams
        ]);
    }
}

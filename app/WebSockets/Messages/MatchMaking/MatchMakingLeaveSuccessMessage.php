<?php

namespace App\WebSockets\Messages\MatchMaking;

class MatchMakingLeaveSuccessMessage
{
    public function toJson(): string
    {
        return json_encode([
            'type' => 'matchmaking_leave_success',
        ]);
    }
}

<?php

namespace App\WebSockets\Messages\MatchMaking;

use App\WebSockets\Messages\WebSocketMessage;

class MatchMakingLeaveSuccessMessage extends WebSocketMessage
{
    public function __construct()
    {
        parent::__construct('matchmaking_leave_success', []);
    }
}

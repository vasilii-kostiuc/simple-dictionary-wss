<?php

namespace App\WebSockets\Messages\Match;

use App\WebSockets\Messages\WebSocketMessage;

class MatchCompletedMessage extends WebSocketMessage
{
    public function __construct(array $data = [])
    {
        parent::__construct('match_completed', $data);
    }
}

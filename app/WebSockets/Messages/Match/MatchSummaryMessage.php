<?php

namespace App\WebSockets\Messages\Match;

use App\WebSockets\Messages\WebSocketMessage;

class MatchSummaryMessage extends WebSocketMessage
{
    public function __construct(array $summaryData = [])
    {
        parent::__construct('match_summary', $summaryData);
    }
}

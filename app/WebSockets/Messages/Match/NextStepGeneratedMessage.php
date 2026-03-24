<?php

namespace App\WebSockets\Messages\Match;

use App\WebSockets\Messages\WebSocketMessage;

class NextStepGeneratedMessage extends WebSocketMessage
{
    public function __construct(array $stepData = [])
    {
        parent::__construct('next_step_generated', $stepData);
    }
}

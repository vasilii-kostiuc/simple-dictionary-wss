<?php

namespace App\WebSockets\Handlers\Internal\MatchMaking;

use App\WebSockets\Handlers\Internal\BaseInternalMatchMakingHandler;

class MatchMakingJoinedHandler extends BaseInternalMatchMakingHandler
{
    public function handle(mixed $payload): void
    {
        info(message: __METHOD__.' Received message with payload: '.json_encode($payload));
        $this->broadcastQueueUpdated();
    }
}

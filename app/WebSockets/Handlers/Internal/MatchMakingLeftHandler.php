<?php

namespace App\WebSockets\Handlers\Internal;

class MatchMakingLeftHandler extends BaseInternalMatchMakingHandler
{
    public function handle(mixed $payload): void
    {
        info(message: __METHOD__.' Received message with payload: '.json_encode($payload));

        $this->broadcastQueueUpdated();
    }
}

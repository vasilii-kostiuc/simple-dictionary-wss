<?php

namespace App\WebSockets\Handlers\Internal;

class MatchMakingLeftHandler extends BaseInternalMatchMakingHandler
{
    public function handle(string $channel, mixed $payload): void
    {
        info(message: __METHOD__.' Received message on channel: '.$channel.' with payload: '.json_encode($payload));

        $this->broadcastQueueUpdated();
    }
}

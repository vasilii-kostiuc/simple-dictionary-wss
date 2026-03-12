<?php

namespace App\WebSockets\Handlers\Internal;

class MatchMakingJoinedHandler extends BaseInternalMatchMakingHandler
{

    public function handle(string $channel, mixed $data): void
    {
        info(message: __METHOD__ . ' Received message on channel: ' . $channel . ' with payload: ' . json_encode($data));

        $this->broadcastQueueUpdated();
    }
}

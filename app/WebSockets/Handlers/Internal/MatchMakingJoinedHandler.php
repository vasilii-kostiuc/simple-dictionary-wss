<?php

namespace App\WebSockets\Handlers\Internal;

use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class MatchMakingJoinedHandler extends BaseInternalMatchMakingHandler
{

    public function handle(string $channel, mixed $data): void
    {  
        info(message: __METHOD__ . ' Received message on channel: ' . $channel . ' with payload: ' . json_encode($data));

        $this->broadcastQueueUpdated();
    }
}

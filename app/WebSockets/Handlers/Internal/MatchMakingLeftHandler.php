<?php

namespace App\WebSockets\Handlers\Internal;

use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class MatchMakingLeftHandler extends BaseInternalMatchMakingHandler
{
    public function handle(ConnectionInterface $from, MessageInterface $msg): void
    {
        $data = json_decode($msg->getPayload(), true);
        $matchParams = $data['match_params'] ?? [];

        $this->broadcastQueueUpdated($matchParams);
    }
}

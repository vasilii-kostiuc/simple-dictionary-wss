<?php

namespace App\WebSockets\Messages\MatchMaking;

use App\WebSockets\Messages\WebSocketMessage;

class MatchMakingQueueUpdatedMessage extends WebSocketMessage
{
    public function __construct(protected array $queue = [])
    {
        parent::__construct(
            'matchmaking.queue.updated',
            [
                'queue' => $this->queue,
            ]
        );
    }

}

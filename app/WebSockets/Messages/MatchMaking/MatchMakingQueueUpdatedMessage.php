<?php

namespace App\WebSockets\Messages\MatchMaking;

class MatchMakingQueueUpdatedMessage
{
    public function __construct(protected array $queue = [])
    {
    }

    public function toJson(): string
    {
        return json_encode([
            'type' => 'matchmaking.queue.updated',
            'data' => [
                'queue' => $this->queue,
            ],
        ]);
    }
}

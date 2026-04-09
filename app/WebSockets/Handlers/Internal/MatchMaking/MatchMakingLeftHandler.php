<?php

namespace App\WebSockets\Handlers\Internal\MatchMaking;

use App\WebSockets\Handlers\Internal\BaseInternalMatchMakingHandler;

class MatchMakingLeftHandler extends BaseInternalMatchMakingHandler
{
    public function handle(mixed $payload): void
    {
        $this->broadcastQueueUpdated();
    }
}

<?php

namespace App\WebSockets\Listeners\MatchMaking;

use App\WebSockets\Events\MatchMaking\MatchMakingJoinedEvent;
use Illuminate\Support\Facades\Redis;

class PublishMatchMakingJoinedListener
{
    public function handle(MatchMakingJoinedEvent $event): void
    {
        Redis::publish('wss.matchmaking.join', json_encode([
            'user_id' => $event->userId,
            'match_params' => $event->matchParams,
        ]));
    }
}

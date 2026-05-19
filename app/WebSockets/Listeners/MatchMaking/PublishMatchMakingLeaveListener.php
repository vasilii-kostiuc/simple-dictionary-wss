<?php

namespace App\WebSockets\Listeners\MatchMaking;

use App\Application\MatchMaking\Events\MatchMakingLeaveEvent;
use Illuminate\Support\Facades\Redis;
use VasiliiKostiuc\PubSubBroker\Messaging\BrokerInterface;

class PublishMatchMakingLeaveListener
{
    private BrokerInterface $messageBroker;

    public function __construct(BrokerInterface $messageBroker)
    {
        $this->messageBroker = $messageBroker;
    }

    public function handle(MatchMakingLeaveEvent $event): void
    {
        $this->messageBroker->publish('wss.matchmaking.leaved', json_encode([
            'type' => 'wss.matchmaking.leaved',
            'data' => [
                'user_id' => $event->userId,
            ],
        ]));

        // Redis::publish('wss.matchmaking.leave', json_encode([
        //     'user_id' => $event->userId,
        // ]));
    }
}

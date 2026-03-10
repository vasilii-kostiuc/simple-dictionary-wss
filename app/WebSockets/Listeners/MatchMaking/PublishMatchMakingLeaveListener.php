<?php

namespace App\WebSockets\Listeners\MatchMaking;

use App\WebSockets\Events\MatchMaking\MatchMakingLeaveEvent;
use Illuminate\Support\Facades\Redis;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerInterface;

class PublishMatchMakingLeaveListener
{
    private MessageBrokerInterface $messageBroker;

    public function __construct(MessageBrokerInterface $messageBroker)
    {
        $this->messageBroker = $messageBroker;
    }


    public function handle(MatchMakingLeaveEvent $event): void
    {
        $this->messageBroker->publish('wss.matchmaking.leave', json_encode([
            'user_id' => $event->userId,
        ]));

        // Redis::publish('wss.matchmaking.leave', json_encode([
        //     'user_id' => $event->userId,
        // ]));
    }
}

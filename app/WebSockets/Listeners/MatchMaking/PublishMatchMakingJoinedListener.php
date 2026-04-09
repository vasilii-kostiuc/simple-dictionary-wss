<?php

namespace App\WebSockets\Listeners\MatchMaking;

use App\Application\MatchMaking\Events\MatchMakingJoinedEvent;
use Illuminate\Support\Facades\Redis;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerInterface;

class PublishMatchMakingJoinedListener
{
    private MessageBrokerInterface $messageBroker;

    public function __construct(MessageBrokerInterface $messageBroker)
    {
        $this->messageBroker = $messageBroker;

    }

    public function handle(MatchMakingJoinedEvent $event): void
    {
        info(__METHOD__.' Publishing MatchMakingJoinedEvent for user_id: '.$event->userId);
        $this->messageBroker->publish('wss.matchmaking.joined', json_encode([
            'type' => 'wss.matchmaking.joined',
            'data' => [
                'user_id' => $event->userId,
                'match_params' => $event->matchParams,
            ],
        ]));

        // Redis::publish('wss.matchmaking.joined', json_encode([
        //     'user_id' => $event->userId,
        //     'match_params' => $event->matchParams,
        // ]));

        info(__METHOD__.' Published MatchMakingJoinedEvent for user_id: '.$event->userId);
    }
}

<?php

namespace App\WebSockets\Listeners\MatchMaking;

use App\Application\MatchMaking\Events\MatchMakingJoinedEvent;
use VasiliiKostiuc\PubSubBroker\Messaging\BrokerInterface;

class PublishMatchMakingJoinedListener
{
    private BrokerInterface $messageBroker;

    public function __construct(BrokerInterface $messageBroker)
    {
        $this->messageBroker = $messageBroker;

    }

    public function handle(MatchMakingJoinedEvent $event): void
    {
        $this->messageBroker->publish('wss.matchmaking.joined', json_encode([
            'type' => 'wss.matchmaking.joined',
            'data' => [
                'user_id' => $event->userId,
                'match_params' => $event->matchParams->toArray(),
            ],
        ]));

    }
}

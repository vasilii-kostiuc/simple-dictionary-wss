<?php

namespace App\WebSockets\Listeners\MatchMaking;

use App\Application\MatchMaking\Events\MatchMakingQueueUpdatedEvent;
use VasiliiKostiuc\PubSubBroker\Messaging\BrokerInterface;

class PublishMatchMakingQueueUpdatedListener
{
    private BrokerInterface $messageBroker;

    public function __construct(BrokerInterface $messageBroker)
    {
        $this->messageBroker = $messageBroker;
    }

    public function handle(MatchMakingQueueUpdatedEvent $event): void
    {
        $this->messageBroker->publish('wss.matchmaking.queue.updated', json_encode([
            'type' => 'wss.matchmaking.queue.updated',
            'data' => [
            ],
        ]));

    }
}

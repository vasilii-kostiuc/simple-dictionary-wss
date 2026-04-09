<?php

namespace App\WebSockets\Listeners\MatchMaking;

use App\Application\MatchMaking\Events\MatchMakingQueueUpdatedEvent;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerInterface;

class PublishMatchMakingQueueUpdatedListener
{
    private MessageBrokerInterface $messageBroker;

    public function __construct(MessageBrokerInterface $messageBroker)
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

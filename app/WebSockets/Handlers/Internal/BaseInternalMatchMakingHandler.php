<?php

namespace App\WebSockets\Handlers\Internal;

use App\WebSockets\Messages\MatchMaking\MatchMakingQueueUpdatedMessage;
use App\Domain\MatchMaking\Contracts\MatchMakingQueueInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;

abstract class BaseInternalMatchMakingHandler implements InternalMessageHandlerInterface
{
    public function __construct(
        protected MatchMakingQueueInterface $matchMakingQueue,
        protected SubscriptionsStorageInterface $subscriptionsStorage
    ) {
    }

    protected function broadcastQueueUpdated(): void
    {
        $queue = array_map(
            fn ($entry) => $entry->toArray(),
            $this->matchMakingQueue->allQueues(),
        );

        foreach ($this->subscriptionsStorage->getConnectionsByChannel('matchmaking.queue') as $conn) {
            $conn->send(new MatchMakingQueueUpdatedMessage($queue));
        }
    }
}

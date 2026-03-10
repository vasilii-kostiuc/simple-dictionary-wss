<?php

namespace App\WebSockets\Handlers\Internal;

use App\WebSockets\Handlers\Client\MessageHandlerInterface;
use App\WebSockets\Messages\MatchMaking\MatchMakingQueueUpdatedMessage;
use App\WebSockets\Storage\MatchMaking\MatchMakingQueueInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;

abstract class BaseInternalMatchMakingHandler implements MessageHandlerInterface
{
    public function __construct(
        protected MatchMakingQueueInterface $matchMakingQueue,
        protected SubscriptionsStorageInterface $subscriptionsStorage
    ) {}

    protected function broadcastQueueUpdated(array $matchParams): void
    {
        $queue = $this->matchMakingQueue->allQueues();
        $message = (new MatchMakingQueueUpdatedMessage($queue))->toJson();

        foreach ($this->subscriptionsStorage->getConnectionsByChannel('matchmaking.queue') as $conn) {
            $conn->send($message);
        }
    }
}

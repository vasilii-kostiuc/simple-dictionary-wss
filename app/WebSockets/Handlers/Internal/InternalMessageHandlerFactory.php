<?php

namespace App\WebSockets\Handlers\Internal;

use App\WebSockets\Storage\MatchMaking\MatchMakingQueueInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;

class InternalMessageHandlerFactory
{
    private MatchMakingQueueInterface $matchMakingQueue;

    private SubscriptionsStorageInterface $subscriptionsStorage;

    public function __construct(
        MatchMakingQueueInterface $matchMakingQueue,
        SubscriptionsStorageInterface $subscriptionsStorage
    ) {
        $this->matchMakingQueue = $matchMakingQueue;
        $this->subscriptionsStorage = $subscriptionsStorage;
    }

    public function create(string $type): InternalMessageHandlerInterface
    {
        return match ($type) {
            'wss.matchmaking.joined' => new MatchMakingJoinedHandler($this->matchMakingQueue, $this->subscriptionsStorage),
            'wss.matchmaking.leaved' => new MatchMakingLeftHandler($this->matchMakingQueue, $this->subscriptionsStorage),
            'wss.matchmaking.matched' => new MatchMakingMatchedHandler($this->matchMakingQueue, $this->subscriptionsStorage),
            default => new class implements InternalMessageHandlerInterface
            {
                public function handle(mixed $payload): void
                {
                    info("Received unknown internal message type with payload: ".json_encode($payload));
                }
            }
        };
    }
}

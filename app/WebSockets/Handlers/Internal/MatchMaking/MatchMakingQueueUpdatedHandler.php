<?php

namespace App\WebSockets\Handlers\Internal\MatchMaking;

use App\WebSockets\Handlers\Internal\BaseInternalMatchMakingHandler;
use App\WebSockets\Storage\MatchMaking\MatchMakingQueueInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use Illuminate\Support\Facades\Log;

class MatchMakingQueueUpdatedHandler extends BaseInternalMatchMakingHandler implements \App\WebSockets\Handlers\Internal\InternalMessageHandlerInterface
{
    public function __construct(
        MatchMakingQueueInterface $matchMakingQueue,
        SubscriptionsStorageInterface $subscriptionsStorage
    ) {
        parent::__construct($matchMakingQueue, $subscriptionsStorage);
    }

    public function handle(mixed $payload): void
    {
        Log::info(__METHOD__.' Received matchmaking.queue.updated event', ['payload' => $payload]);
        $this->broadcastQueueUpdated();
    }
}

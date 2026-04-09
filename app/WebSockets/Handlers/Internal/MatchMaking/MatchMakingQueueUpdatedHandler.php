<?php

namespace App\WebSockets\Handlers\Internal\MatchMaking;

use App\WebSockets\Handlers\Internal\BaseInternalMatchMakingHandler;
use App\Domain\MatchMaking\Contracts\MatchMakingQueueInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;

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
        $this->broadcastQueueUpdated();
    }
}

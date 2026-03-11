<?php

namespace App\WebSockets\Handlers\Client\MatchMaking;

use App\WebSockets\Handlers\Client\Subscription\SubscribeMessageHandler;
use App\WebSockets\Messages\MatchMaking\MatchMakingQueueUpdatedMessage;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use App\WebSockets\Storage\MatchMaking\MatchMakingQueueInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class MatchMakingSubscribeHandler extends SubscribeMessageHandler
{
    public function __construct(
        SubscriptionsStorageInterface $subscriptionsStorage,
        ClientsStorageInterface $clientsStorage,
        private readonly MatchMakingQueueInterface $matchMakingQueue,
    ) {
        parent::__construct($subscriptionsStorage, $clientsStorage);
    }

    public function handle(ConnectionInterface $conn, MessageInterface $message): void
    {
        parent::handle($conn, $message);

        $conn->send(new MatchMakingQueueUpdatedMessage(
            $this->matchMakingQueue->allQueues()
        ));
    }
}
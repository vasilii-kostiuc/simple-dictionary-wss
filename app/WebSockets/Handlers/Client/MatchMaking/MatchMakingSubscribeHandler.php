<?php

namespace App\WebSockets\Handlers\Client\MatchMaking;

use App\WebSockets\Handlers\Client\Subscription\SubscribeMessageHandler;
use App\WebSockets\Messages\MatchMaking\MatchMakingQueueUpdatedMessage;
use App\WebSockets\Sender\WebSocketMessageSenderInterface;
use App\WebSockets\Storage\Clients\ClientRegistryInterface;
use App\Domain\MatchMaking\Contracts\MatchMakingQueueInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use App\WebSockets\Subscription\SubscriptionChannelPolicy;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class MatchMakingSubscribeHandler extends SubscribeMessageHandler
{
    public function __construct(
        SubscriptionsStorageInterface $subscriptionsStorage,
        ClientRegistryInterface $clientRegistry,
        SubscriptionChannelPolicy $subscriptionChannelPolicy,
        private readonly MatchMakingQueueInterface $matchMakingQueue,
        private readonly WebSocketMessageSenderInterface $sender,
    ) {
        parent::__construct($subscriptionsStorage, $clientRegistry, $subscriptionChannelPolicy);
    }

    public function handle(ConnectionInterface $conn, MessageInterface $message): void
    {
        parent::handle($conn, $message);

        $this->sender->sendToConnection($conn, new MatchMakingQueueUpdatedMessage(
            $this->matchMakingQueue->allQueues()
        ));
    }
}

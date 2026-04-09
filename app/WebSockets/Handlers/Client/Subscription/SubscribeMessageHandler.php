<?php

namespace App\WebSockets\Handlers\Client\Subscription;

use App\WebSockets\Handlers\Client\MessageHandlerInterface;
use App\WebSockets\Messages\ErrorMessage;
use App\WebSockets\Messages\Subscription\SubscribeSuccessMessage;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use App\WebSockets\Subscription\SubscriptionChannelPolicy;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class SubscribeMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        protected readonly SubscriptionsStorageInterface $subscriptionsStorage,
        protected readonly ClientsStorageInterface $clientsStorage,
        protected readonly SubscriptionChannelPolicy $subscriptionChannelPolicy,
    ) {
    }

    public function handle(ConnectionInterface $from, MessageInterface $msg): void
    {
        $payload = json_decode($msg->getPayload(), true);
        $data = $payload['data'] ?? [];
        $channel = $data['channel'] ?? '';

        if (empty($channel)) {
            $from->send(new ErrorMessage('channel_is_required', $payload));

            return;
        }

        if (! $this->subscriptionChannelPolicy->canSubscribe($channel)) {
            $from->send(new ErrorMessage('channel_is_not_allowed', $payload));

            return;
        }

        $this->subscriptionsStorage->subscribe($from, $channel);

        $from->send(new SubscribeSuccessMessage($channel));
    }
}

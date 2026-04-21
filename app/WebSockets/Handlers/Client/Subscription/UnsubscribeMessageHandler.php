<?php

namespace App\WebSockets\Handlers\Client\Subscription;

use App\Infrastructure\Metrics\WsMetrics;
use App\WebSockets\Handlers\Client\MessageHandlerInterface;
use App\WebSockets\Messages\ErrorMessage;
use App\WebSockets\Messages\Subscription\UnsubscribeSuccessMessage;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use App\WebSockets\Subscription\SubscriptionChannelPolicy;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class UnsubscribeMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        protected readonly SubscriptionsStorageInterface $subscriptionsStorage,
        protected readonly SubscriptionChannelPolicy $subscriptionChannelPolicy,
        protected readonly WsMetrics $metrics,
    ) {}

    public function handle(ConnectionInterface $from, MessageInterface $msg): void
    {
        $payload = json_decode($msg->getPayload(), true);
        $data = $payload['data'] ?? [];
        $channel = $data['channel'] ?? '';

        if (empty($channel)) {
            $this->metrics->subscriptionAttempted('other', 'unsubscribe', 'invalid');
            $from->send(new ErrorMessage('channel_is_required', $payload));

            return;
        }

        if (! $this->subscriptionChannelPolicy->canUnsubscribe($channel)) {
            $this->metrics->subscriptionAttempted($channel, 'unsubscribe', 'denied');
            $from->send(new ErrorMessage('channel_is_not_allowed', $payload));

            return;
        }

        $changed = $this->subscriptionsStorage->unsubscribe($from, $channel);
        $this->metrics->subscriptionAttempted($channel, 'unsubscribe', $changed ? 'success' : 'noop');

        if ($changed) {
            $this->metrics->activeSubscriptionRemoved($channel);
        }

        $from->send(new UnsubscribeSuccessMessage($channel));
    }
}

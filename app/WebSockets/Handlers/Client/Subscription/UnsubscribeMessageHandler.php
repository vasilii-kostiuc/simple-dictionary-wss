<?php

namespace App\WebSockets\Handlers\Client\Subscription;

use App\WebSockets\Handlers\Client\MessageHandlerInterface;
use App\WebSockets\Messages\ErrorMessage;
use App\WebSockets\Messages\Subscription\UnsubscribeSuccessMessage;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class UnsubscribeMessageHandler implements MessageHandlerInterface
{
    protected SubscriptionsStorageInterface $subscriptionsStorage;

    protected ClientsStorageInterface $clientsStorage;

    protected array $allowedChannels = [
        'training',
        'matchmaking.queue',
    ];

    public function __construct(SubscriptionsStorageInterface $subscriptionsStorage, ClientsStorageInterface $clientsStorage)
    {
        $this->subscriptionsStorage = $subscriptionsStorage;
        $this->clientsStorage = $clientsStorage;
    }

    public function handle(ConnectionInterface $from, MessageInterface $msg): void
    {
        info(__METHOD__);
        info($msg);
        $payload = json_decode($msg->getPayload(), true);
        $data = $payload['data'] ?? [];
        $channel = $data['channel'] ?? '';

        $userId = $this->clientsStorage->getUserIdByConnection($from);

        if (empty($channel)) {
            $from->send(new ErrorMessage('channel_is_required', $payload));

            return;
        }

        if (! $this->isAllowedChannel($channel)) {
            $from->send(new ErrorMessage('channel_is_not_allowed', $payload));

            return;
        }

        $this->subscriptionsStorage->unsubscribe($from, $channel);
        $from->send(new UnsubscribeSuccessMessage($channel));
    }

    protected function isAllowedChannel(string $channel): bool
    {
        $parts = explode('.', $channel, 2);
        $channelType = $parts[0] ?? '';

        return in_array($channelType, $this->allowedChannels, true);
    }
}

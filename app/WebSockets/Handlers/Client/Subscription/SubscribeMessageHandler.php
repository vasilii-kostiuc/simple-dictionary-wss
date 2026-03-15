<?php

namespace App\WebSockets\Handlers\Client\Subscription;

use App\WebSockets\Handlers\Client\MessageHandlerInterface;
use App\WebSockets\Messages\ErrorMessage;
use App\WebSockets\Messages\Subscription\SubscribeSuccessMessage;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class SubscribeMessageHandler implements MessageHandlerInterface
{
    protected SubscriptionsStorageInterface $subscriptionsStorage;

    protected ClientsStorageInterface $clientsStorage;

    protected array $allowedChannels = [
        'training.*',
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

        $this->subscriptionsStorage->subscribe($from, $channel);

        $from->send(new SubscribeSuccessMessage($channel));
    }

    protected function isAllowedChannel(string $channel): bool
    {
        foreach ($this->allowedChannels as $pattern) {
            if (str_ends_with($pattern, '.*')) {
                $prefix = substr($pattern, 0, -2);
                if ($channel === $prefix || str_starts_with($channel, $prefix . '.')) {
                    return true;
                }
            } elseif ($channel === $pattern) {
                return true;
            }
        }

        return false;
    }
}

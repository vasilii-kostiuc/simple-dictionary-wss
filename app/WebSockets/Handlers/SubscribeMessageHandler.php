<?php

namespace App\WebSockets\Handlers;

use App\WebSockets\Handlers\MessageHandlerInterface;
use App\WebSockets\Messages\ErrorMessage;
use App\WebSockets\Messages\SubscribeSuccessMessage;
use App\WebSockets\Storage\Clients\AuthorizedClientsStorage;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use App\WebSockets\Storage\Subscriptions\SubscriptionsStorageInterface;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class SubscribeMessageHandler implements MessageHandlerInterface
{
    protected SubscriptionsStorageInterface $subscriptionsStorage;
    protected ClientsStorageInterface $clientsStorage;

    protected array $allowedChannels = [
        'training'
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
        $msgPayload = json_decode($msg->getPayload());
        $channel = $msgPayload->channel ?? "";

        $userId = $this->clientsStorage->getUserIdByConnection($from);

        if ($userId === null) {
            $from->send(new ErrorMessage('not_authorized', $msgPayload));
            return;
        }

        if (empty($channel)) {
            $from->send(new ErrorMessage('channel_is_required', $msgPayload));
            return;
        }

        if (!$this->isAllowedChannel($channel)) {
            $from->send(new ErrorMessage('channel_is_not_allowed', $msgPayload));
            return;
        }

        $this->subscriptionsStorage->subscribe($from, $channel);
        $from->send(new SubscribeSuccessMessage($channel));
    }

    protected function isAllowedChannel(string $channel): bool
    {
        $parts = explode('.', $channel, 2);
        $channelType = $parts[0] ?? '';

        return in_array($channelType, $this->allowedChannels, true);
    }
}

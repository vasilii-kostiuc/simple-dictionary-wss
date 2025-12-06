<?php

namespace App\WebSockets\Storage;

use Ratchet\ConnectionInterface;

class SubscriptionsStorage implements SubscriptionsStorageInterface
{
    protected array $channelSubscribers = []; // [channel => [connId => ConnectionInterface]]
    protected array $connectionChannels = []; // [connId => [channel1, channel2, ...]]

    public function subscribe(ConnectionInterface $conn, string $channel)
    {

        $connId = $conn->resourceId;
        $this->channelSubscribers[$channel][$connId] = $conn;
        $this->connectionChannels[$connId][$channel] = true;
    }

    public function unsubscribe(ConnectionInterface $conn, string $channel)
    {
        $connId = $conn->resourceId;
        unset($this->channelSubscribers[$channel][$connId]);
        unset($this->connectionChannels[$connId][$channel]);
        if (empty($this->channelSubscribers[$channel])) {
            unset($this->channelSubscribers[$channel]);
        }
        if (empty($this->connectionChannels[$connId])) {
            unset($this->connectionChannels[$connId]);
        }
    }

    public function getConnectionsByChannel(string $channel): array
    {
        return $this->channelSubscribers[$channel] ?? [];
    }

    public function getChannelsByConnection(ConnectionInterface $conn): array
    {
        $connId = $conn->resourceId;
        return array_keys($this->connectionChannels[$connId] ?? []);
    }

    public function countByChannel(string $channel): int
    {
        return isset($this->channelSubscribers[$channel])
            ? count($this->channelSubscribers[$channel])
            : 0;
    }
}

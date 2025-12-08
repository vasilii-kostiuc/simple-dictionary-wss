<?php

namespace App\WebSockets\Storage\Subscriptions;

use Ratchet\ConnectionInterface;

interface SubscriptionsStorageInterface
{
    public function subscribe(ConnectionInterface $conn, string $channel);

    public function unsubscribe(ConnectionInterface $conn, string $channel);

    public function getConnectionsByChannel(string $channel): array;

    public function getChannelsByConnection(ConnectionInterface $conn): array;

    public function countByChannel(string $channel): int;
}

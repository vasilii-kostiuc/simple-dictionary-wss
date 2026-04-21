<?php

namespace App\WebSockets\Storage\Subscriptions;

use Ratchet\ConnectionInterface;

interface SubscriptionsStorageInterface
{
    public function subscribe(ConnectionInterface $conn, string $channel): bool;

    public function unsubscribe(ConnectionInterface $conn, string $channel): bool;

    public function unsubscribeAll(ConnectionInterface $conn): void;

    public function getConnectionsByChannel(string $channel): array;

    public function getChannelsByConnection(ConnectionInterface $conn): array;

    public function countByChannel(string $channel): int;
}

<?php

namespace App\WebSockets\Storage;

use Ratchet\ConnectionInterface;

interface ClientsStorageInterface
{
    public function add(int|string $userId, ConnectionInterface $connection): void;

    public function get(int|string $userId): array;

    public function remove(int|string $userId, ConnectionInterface $connection): void;

    public function has(int|string $userId): bool;

    public function all(): array;

    public function getUserIdByConnection(ConnectionInterface $conn): int|string|null;
}

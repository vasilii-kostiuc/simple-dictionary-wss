<?php

namespace App\WebSockets\Storage;

use Ratchet\ConnectionInterface;

class AuthorizedClientsStorage implements ClientsStorageInterface
{
    /**
     * @var array user_id => array of connections
     */
    protected array $clients = [];

    protected array $connectionToUserId = [];

    public function add(int|string $userId, ConnectionInterface $connection): void
    {
        $connectionHash = $this->getConnectionHash($connection);

        if (!isset($this->clients[$userId])) {
            $this->clients[$userId] = [];
            $this->connectionToUserId[$connectionHash] = $userId;
        }

        $this->clients[$userId][$connectionHash] = $connection;
    }

    protected function getConnectionHash(ConnectionInterface $connection): string
    {
        return spl_object_hash($connection);
    }

    public function get(int|string $userId): array
    {
        return $this->clients[$userId] ?? [];
    }

    public function remove(int|string $userId, ConnectionInterface $connection): void
    {
        $connectionHash = $this->getConnectionHash($connection);
        if (isset($this->clients[$userId][$connectionHash])) {
            unset($this->clients[$userId][$connectionHash]);
            unset($this->connectionToUserId[$connectionHash]);

            if (empty($this->clients[$userId])) {
                unset($this->clients[$userId]);
            }
        }
    }


    public function has(int|string $userId): bool
    {
        return !empty($this->clients[$userId]);
    }


    public function all(): array
    {
        return $this->clients;
    }

    public function getUserIdByConnection(ConnectionInterface $conn): int|string|null
    {
        $connectionHash = $this->getConnectionHash($conn);
        return $this->connectionToUserId[$connectionHash] ?? null;
    }


}

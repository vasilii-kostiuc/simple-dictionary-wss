<?php

namespace App\WebSockets\Storage;

use Ratchet\ConnectionInterface;

class AuthorizedClientsStorage implements ClientsStorageInterface
{
    /**
     * @var array user_id => array of connections
     */
    protected array $clients = [];

    public function add(int|string $userId, ConnectionInterface $connection): void
    {
        if (!isset($this->clients[$userId])) {
            $this->clients[$userId] = [];
        }
        // Чтобы избежать дублей connection:
        $connection_spl_hash = spl_object_hash($connection);
        $this->clients[$userId][$connection_spl_hash] = $connection;
    }


    public function get(int|string $userId): array
    {
        return $this->clients[$userId] ?? [];
    }

    public function remove(int|string $userId, ConnectionInterface $connection): void
    {
        $connection_spl_hash = spl_object_hash($connection);
        if (isset($this->clients[$userId][$connection_spl_hash])) {
            unset($this->clients[$userId][$connection_spl_hash]);
            // Чистим, если последнее соединение удалено
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
}

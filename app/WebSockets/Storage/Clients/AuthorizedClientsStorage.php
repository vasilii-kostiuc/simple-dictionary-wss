<?php

namespace App\WebSockets\Storage\Clients;

use App\WebSockets\DTO\UserData;
use Ratchet\ConnectionInterface;

class AuthorizedClientsStorage implements ClientsStorageInterface
{
    private array $clients = [];

    public function add(ConnectionInterface $conn, UserData $userData): void
    {
        $this->clients[$conn->resourceId] = [
            'connection' => $conn,
            'userData' => $userData,
        ];
    }

    public function getUserIdByConnection(ConnectionInterface $conn): ?int
    {
        return $this->clients[$conn->resourceId]['userData']?->id ?? null;
    }

    public function getUserData(ConnectionInterface $conn): ?UserData
    {
        return $this->clients[$conn->resourceId]['userData'] ?? null;
    }

    public function getConnectionsByUserId(int $userId): array
    {
        $connections = [];
        foreach ($this->clients as $client) {
            if ($client['userData']->id === $userId) {
                $connections[] = $client['connection'];
            }
        }

        return $connections;
    }

    public function remove(int $userId, ConnectionInterface $conn): void
    {
        unset($this->clients[$conn->resourceId]);
    }
}

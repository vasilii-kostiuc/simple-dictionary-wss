<?php

namespace App\WebSockets\Storage\Clients;

use App\WebSockets\DTO\UserData;
use Ratchet\ConnectionInterface;

class GuestClientsStorage implements ClientsStorageInterface
{
    private array $clients = [];

    public function add(ConnectionInterface $conn, UserData $userData): void
    {
        $this->clients[$conn->resourceId] = [
            'connection' => $conn,
            'userData' => $userData,
        ];
    }

    public function getIdentifierByConnection(ConnectionInterface $conn): ?string
    {
        return $this->clients[$conn->resourceId]['userData']?->guestId ?? null;
    }

    public function getUserData(ConnectionInterface $conn): ?UserData
    {
        return $this->clients[$conn->resourceId]['userData'] ?? null;
    }

    public function getConnectionsByIdentifier(string $identifier): array
    {
        $connections = [];
        foreach ($this->clients as $client) {
            if ($client['userData']->guestId === $identifier) {
                $connections[] = $client['connection'];
            }
        }

        return $connections;
    }

    public function remove(ConnectionInterface $conn): void
    {
        unset($this->clients[$conn->resourceId]);
    }
}

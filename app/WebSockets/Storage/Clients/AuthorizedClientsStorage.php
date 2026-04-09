<?php

namespace App\WebSockets\Storage\Clients;

use App\Domain\Shared\DTO\ConnectedUser;
use Ratchet\ConnectionInterface;

class AuthorizedClientsStorage implements ClientsStorageInterface
{
    private array $clients = [];

    public function add(ConnectionInterface $conn, ConnectedUser $userData): void
    {
        $this->clients[$conn->resourceId] = [
            'connection' => $conn,
            'userData' => $userData,
        ];
    }

    public function getIdentifierByConnection(ConnectionInterface $conn): ?string
    {
        $userData = $this->clients[$conn->resourceId]['userData'] ?? null;

        return $userData !== null ? $userData->getIdentifier() : null;
    }

    public function getUserData(ConnectionInterface $conn): ?ConnectedUser
    {
        return $this->clients[$conn->resourceId]['userData'] ?? null;
    }

    public function getConnectionsByIdentifier(string $identifier): array
    {
        $connections = [];
        foreach ($this->clients as $client) {
            if ($client['userData']->getIdentifier() === $identifier) {
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

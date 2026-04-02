<?php

namespace App\WebSockets\Storage\Clients;

use App\WebSockets\DTO\UserData;
use Ratchet\ConnectionInterface;

class CompositeClientsStorage implements ClientsStorageInterface
{
    public function __construct(
        private readonly AuthorizedClientsStorage $authorized,
        private readonly GuestClientsStorage      $guests,
    ) {
    }

    public function add(ConnectionInterface $conn, UserData $userData): void
    {
        if ($userData->isGuest()) {
            $this->guests->add($conn, $userData);
        } else {
            $this->authorized->add($conn, $userData);
        }
    }

    public function getIdentifierByConnection(ConnectionInterface $conn): ?string
    {
        return $this->authorized->getIdentifierByConnection($conn)
            ?? $this->guests->getIdentifierByConnection($conn);
    }

    public function getUserData(ConnectionInterface $conn): ?UserData
    {
        return $this->authorized->getUserData($conn)
            ?? $this->guests->getUserData($conn);
    }

    public function getConnectionsByIdentifier(string $identifier): array
    {
        $connections = $this->authorized->getConnectionsByIdentifier($identifier);

        return $connections ?: $this->guests->getConnectionsByIdentifier($identifier);
    }

    public function remove(ConnectionInterface $conn): void
    {
        $this->authorized->remove($conn);
        $this->guests->remove($conn);
    }
}

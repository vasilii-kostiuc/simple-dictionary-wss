<?php

namespace App\WebSockets\Storage\Clients;

use App\Domain\Shared\DTO\ConnectedUser;
use Ratchet\ConnectionInterface;

interface ClientsStorageInterface
{
    public function add(ConnectionInterface $conn, ConnectedUser $userData): void;

    public function getIdentifierByConnection(ConnectionInterface $conn): ?string;

    public function getUserData(ConnectionInterface $conn): ?ConnectedUser;

    public function getConnectionsByIdentifier(string $identifier): array;

    public function remove(ConnectionInterface $conn): void;
}

<?php

namespace App\WebSockets\Storage\Clients;

use App\WebSockets\DTO\UserData;
use Ratchet\ConnectionInterface;

interface ClientsStorageInterface
{
    public function add(ConnectionInterface $conn, UserData $userData): void;

    public function getIdentifierByConnection(ConnectionInterface $conn): ?string;

    public function getUserData(ConnectionInterface $conn): ?UserData;

    public function getConnectionsByIdentifier(string $identifier): array;

    public function remove(ConnectionInterface $conn): void;
}

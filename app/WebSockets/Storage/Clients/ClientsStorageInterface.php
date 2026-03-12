<?php

namespace App\WebSockets\Storage\Clients;

use App\WebSockets\DTO\UserData;
use Ratchet\ConnectionInterface;

interface ClientsStorageInterface
{
    public function add(ConnectionInterface $conn, UserData $userData): void;

    public function getUserIdByConnection(ConnectionInterface $conn): ?int;

    public function getUserData(ConnectionInterface $conn): ?UserData;

    public function getConnectionByUserId(int $userId): ?ConnectionInterface;

    public function remove(int $userId, ConnectionInterface $conn): void;
}

<?php

namespace App\WebSockets\Sender;

use App\WebSockets\Messages\WebSocketMessage;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use Ratchet\ConnectionInterface;

class WebSocketMessageSender implements WebSocketMessageSenderInterface
{
    public function __construct(
        private readonly ClientsStorageInterface $clientsStorage,
    ) {}

    public function sendToUser(int $userId, WebSocketMessage $message): void
    {
        foreach ($this->clientsStorage->getConnectionsByUserId($userId) as $connection) {
            $connection->send($message);
        }
    }

    public function sendToConnection(ConnectionInterface $conn, WebSocketMessage $message): void
    {
        $userData = $this->clientsStorage->getUserData($conn);
        if (! $userData) {
            $this->sendToUser($userData->id, $message);

            return;
        }

        $conn->send($message);
    }
}

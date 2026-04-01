<?php

namespace App\WebSockets\Sender;

use App\WebSockets\Messages\WebSocketMessage;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use Ratchet\ConnectionInterface;

class WebSocketMessageSender implements WebSocketMessageSenderInterface
{
    public function __construct(
        private readonly ClientsStorageInterface $clientsStorage,
    ) {
    }

    public function sendToIdentifier(string $identifier, WebSocketMessage $message): void
    {
        foreach ($this->clientsStorage->getConnectionsByIdentifier($identifier) as $connection) {
            $connection->send($message);
        }
    }

    public function sendToConnection(ConnectionInterface $conn, WebSocketMessage $message): void
    {
        $identifier = $this->clientsStorage->getIdentifierByConnection($conn);
        if ($identifier !== null) {
            foreach ($this->clientsStorage->getConnectionsByIdentifier($identifier) as $connection) {
                $connection->send($message);
            }

            return;
        }

        $conn->send($message);
    }
}

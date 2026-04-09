<?php

namespace App\WebSockets\Sender;

use App\WebSockets\Messages\WebSocketMessage;
use App\WebSockets\Storage\Clients\ClientRegistryInterface;
use Ratchet\ConnectionInterface;

class WebSocketMessageSender implements WebSocketMessageSenderInterface
{
    public function __construct(
        private readonly ClientRegistryInterface $clientRegistry,
    ) {
    }

    public function sendToIdentifier(string $identifier, WebSocketMessage $message): void
    {
        foreach ($this->clientRegistry->getConnectionsByIdentifier($identifier) as $connection) {
            $connection->send($message);
        }
    }

    public function sendToConnection(ConnectionInterface $conn, WebSocketMessage $message): void
    {
        $identifier = $this->clientRegistry->getIdentifierByConnection($conn);
        if ($identifier !== null) {
            foreach ($this->clientRegistry->getConnectionsByIdentifier($identifier) as $connection) {
                $connection->send($message);
            }

            return;
        }

        $conn->send($message);
    }
}
